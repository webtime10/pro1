<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Делает физически уникальный файл изображения для каждого товара
 * (разный бинарный хэш), чтобы честно тестировать скорость загрузки без кэша браузера.
 *
 * php artisan catalog:unique-product-images
 * php artisan catalog:unique-product-images --chunk=100 --force
 */
class UniqueProductImagesCommand extends Command
{
    protected $signature = 'catalog:unique-product-images
                            {--chunk=200 : Размер пакета товаров}
                            {--force : Пересоздать даже если путь уже catalog/unique/...}
                            {--dry-run : Только показать план, без записи на диск и в БД}';

    protected $description = 'Создать уникальные копии картинок товаров (GD micro-diff) и обновить поле image';

    private const DEST_DIR = 'catalog/unique';

    /** @var array<string, string> path => binary contents */
    private array $sourceCache = [];

    public function handle(): int
    {
        if (! extension_loaded('gd')) {
            $this->error('Нужно расширение PHP GD.');

            return self::FAILURE;
        }

        $chunk = max(1, (int) $this->option('chunk'));
        $force = (bool) $this->option('force');
        $dryRun = (bool) $this->option('dry-run');

        $destAbs = storage_path('app/public/'.self::DEST_DIR);
        if (! $dryRun && ! is_dir($destAbs) && ! mkdir($destAbs, 0755, true) && ! is_dir($destAbs)) {
            $this->error('Не удалось создать папку: '.$destAbs);

            return self::FAILURE;
        }

        $query = Product::query()
            ->whereNotNull('image')
            ->where('image', '!=', '')
            ->orderBy('id');

        if (! $force) {
            $query->where('image', 'not like', self::DEST_DIR.'/%');
        }

        $total = (clone $query)->count();
        if ($total === 0) {
            $this->info('Нечего обрабатывать (нет товаров или все уже unique). Используйте --force для пересоздания.');

            return self::SUCCESS;
        }

        $this->info(($dryRun ? '[dry-run] ' : '')."Товаров к обработке: {$total}, пакет: {$chunk}");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $ok = 0;
        $skip = 0;
        $fail = 0;

        Product::withoutSyncingToSearch(function () use ($query, $chunk, $dryRun, $destAbs, $bar, &$ok, &$skip, &$fail) {
            $query->select(['id', 'image'])->chunkById($chunk, function ($products) use ($dryRun, $destAbs, $bar, &$ok, &$skip, &$fail) {
                $updates = [];

                foreach ($products as $product) {
                    try {
                        $sourceAbs = $this->resolveSourcePath((string) $product->image);
                        if ($sourceAbs === null) {
                            $skip++;
                            $bar->advance();

                            continue;
                        }

                        $relative = self::DEST_DIR.'/img_'.$product->id.'.jpg';
                        $destFile = $destAbs.'/img_'.$product->id.'.jpg';

                        if (! $dryRun) {
                            $this->writeUniqueCopy($sourceAbs, $destFile, (int) $product->id);
                            $updates[$product->id] = $relative;
                        }

                        $ok++;
                    } catch (Throwable $e) {
                        $fail++;
                        $this->newLine();
                        $this->warn("Товар #{$product->id}: {$e->getMessage()}");
                    }

                    $bar->advance();
                }

                if (! $dryRun && $updates !== []) {
                    $this->bulkUpdateImages($updates);
                }
            });
        });

        $bar->finish();
        $this->newLine(2);
        $this->info("Готово. OK: {$ok}, пропущено (нет файла): {$skip}, ошибок: {$fail}");
        if (! $dryRun && $ok > 0) {
            $this->line('Папка: storage/app/public/'.self::DEST_DIR);
            $this->line('URL: /storage/catalog/unique/img_{id}.jpg');
        }

        return $fail > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function resolveSourcePath(string $image): ?string
    {
        $image = ltrim(str_replace('\\', '/', $image), '/');

        // Уже unique — берём исходник из публичного storage по текущему пути
        // (при --force пересоздаём из существующего unique-файла).
        $candidates = [
            storage_path('app/public/'.$image),
            public_path($image),
            public_path('storage/'.$image),
        ];

        foreach ($candidates as $path) {
            if (is_file($path) && is_readable($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Копия с микро-изменением через GD: кроп 1px + яркость + JPEG quality 65–70
     * + пиксель, завязанный на id — гарантированно уникальный бинарный хэш.
     */
    private function writeUniqueCopy(string $sourceAbs, string $destAbs, int $productId): void
    {
        $binary = $this->sourceCache[$sourceAbs] ??= (string) file_get_contents($sourceAbs);
        $src = @imagecreatefromstring($binary);
        if ($src === false) {
            throw new \RuntimeException('GD не смог прочитать: '.$sourceAbs);
        }

        $w = imagesx($src);
        $h = imagesy($src);
        if ($w < 2 || $h < 2) {
            imagedestroy($src);
            throw new \RuntimeException('Слишком маленькое изображение: '.$sourceAbs);
        }

        // Микро-кроп: случайно срезаем 1px с одного края
        $side = random_int(0, 3);
        $cropW = $w - ($side === 1 || $side === 3 ? 1 : 0);
        $cropH = $h - ($side === 0 || $side === 2 ? 1 : 0);
        $cropX = $side === 3 ? 1 : 0;
        $cropY = $side === 0 ? 1 : 0;

        $dst = imagecreatetruecolor($cropW, $cropH);
        if ($dst === false) {
            imagedestroy($src);
            throw new \RuntimeException('Не удалось создать холст GD');
        }

        // Белый фон на случай прозрачности PNG
        $white = imagecolorallocate($dst, 255, 255, 255);
        imagefilledrectangle($dst, 0, 0, $cropW, $cropH, $white);
        imagecopy($dst, $src, 0, 0, $cropX, $cropY, $cropW, $cropH);
        imagedestroy($src);

        // Лёгкая яркость ±1…3 (около 1%)
        $brightness = random_int(0, 1) === 1 ? random_int(1, 3) : -random_int(1, 3);
        imagefilter($dst, IMG_FILTER_BRIGHTNESS, $brightness);

        // Пиксель, уникальный для товара — страховка от коллизии хэшей
        $r = ($productId * 37) % 256;
        $g = ($productId * 73) % 256;
        $b = ($productId * 91) % 256;
        $px = imagecolorallocate($dst, $r, $g, $b);
        imagesetpixel($dst, $cropW - 1, $cropH - 1, $px);

        $quality = random_int(65, 70);
        $ok = imagejpeg($dst, $destAbs, $quality);
        imagedestroy($dst);

        if (! $ok || ! is_file($destAbs)) {
            throw new \RuntimeException('Не удалось сохранить: '.$destAbs);
        }
    }

    /**
     * @param  array<int, string>  $updates  id => relative path
     */
    private function bulkUpdateImages(array $updates): void
    {
        $cases = [];
        $ids = [];
        $bindings = [];

        foreach ($updates as $id => $path) {
            $cases[] = 'WHEN ? THEN ?';
            $bindings[] = $id;
            $bindings[] = $path;
            $ids[] = $id;
        }

        $idPlaceholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = 'UPDATE products SET image = CASE id '.implode(' ', $cases).' END, updated_at = ? WHERE id IN ('.$idPlaceholders.')';
        $bindings[] = now()->toDateTimeString();
        foreach ($ids as $id) {
            $bindings[] = $id;
        }

        DB::update($sql, $bindings);
    }
}
