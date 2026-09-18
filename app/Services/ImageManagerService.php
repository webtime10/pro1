<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Файловый менеджер изображений, как OpenCart common/filemanager.
 * Корень: storage/app/public/catalog → URL /storage/catalog/...
 */
class ImageManagerService
{
    public const PER_PAGE = 16;

    public const EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    public function diskRoot(): string
    {
        return storage_path('app/public');
    }

    public function catalogRoot(): string
    {
        $root = $this->diskRoot().DIRECTORY_SEPARATOR.'catalog';

        if (! is_dir($root) && ! mkdir($root, 0775, true) && ! is_dir($root)) {
            throw new RuntimeException('Cannot create catalog image directory');
        }

        return $root;
    }

    public function placeholderUrl(): string
    {
        return asset('assets/admin/img/no_image.svg');
    }

    public function sanitizeDirectory(?string $directory): string
    {
        $directory = str_replace('\\', '/', (string) $directory);
        $parts = [];

        foreach (explode('/', $directory) as $part) {
            $part = trim($part);
            if ($part === '' || $part === '.' || $part === '..') {
                continue;
            }
            $parts[] = $part;
        }

        return implode('/', $parts);
    }

    public function sanitizeDomId(?string $id): string
    {
        $id = (string) $id;

        return preg_match('/^[A-Za-z][A-Za-z0-9_-]*$/', $id) ? $id : '';
    }

    public function absoluteDirectory(string $relative): string
    {
        $relative = $this->sanitizeDirectory($relative);
        $catalog = $this->realCatalogRoot();
        $absolute = $relative === '' ? $catalog : $catalog.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative);

        if (! is_dir($absolute)) {
            throw new RuntimeException('Directory not found');
        }

        return $this->assertInside($absolute, $catalog);
    }

    /**
     * @return array{items: LengthAwarePaginator, directory: string, parent: string}
     */
    public function paginate(?string $directory, ?string $filterName, int $page): array
    {
        $directory = $this->sanitizeDirectory($directory);
        $absolute = $this->absoluteDirectory($directory);
        $filterName = trim(str_replace(['*', '/', '\\'], '', (string) $filterName));
        $filterLower = mb_strtolower($filterName);
        $items = [];

        foreach (scandir($absolute) ?: [] as $name) {
            if ($name === '.' || $name === '..' || str_starts_with($name, '.')) {
                continue;
            }

            if ($filterLower !== '' && ! str_starts_with(mb_strtolower($name), $filterLower)) {
                continue;
            }

            $full = $absolute.DIRECTORY_SEPARATOR.$name;
            $relativeFromDisk = $this->relativeFromDisk($full);

            if (is_dir($full)) {
                $childDir = ltrim(substr($relativeFromDisk, strlen('catalog')), '/');
                $items[] = [
                    'type' => 'directory',
                    'name' => $name,
                    'path' => $relativeFromDisk,
                    'directory' => $childDir,
                    'thumb' => '',
                    'href' => '',
                ];
                continue;
            }

            if (! is_file($full) || ! $this->isAllowedFilename($name)) {
                continue;
            }

            $items[] = [
                'type' => 'image',
                'name' => $name,
                'path' => $relativeFromDisk,
                'directory' => $directory,
                'thumb' => $this->thumbUrl($relativeFromDisk, 100, 100),
                'href' => $this->publicUrl($relativeFromDisk),
            ];
        }

        usort($items, function (array $a, array $b) {
            if ($a['type'] !== $b['type']) {
                return $a['type'] === 'directory' ? -1 : 1;
            }

            return strcasecmp($a['name'], $b['name']);
        });

        $page = max(1, $page);
        $total = count($items);
        $slice = array_slice($items, ($page - 1) * self::PER_PAGE, self::PER_PAGE);

        $paginator = new LengthAwarePaginator($slice, $total, self::PER_PAGE, $page);

        $parent = '';
        if ($directory !== '') {
            $parent = str_contains($directory, '/') ? dirname($directory) : '';
            if ($parent === '.') {
                $parent = '';
            }
        }

        return [
            'items' => $paginator,
            'directory' => $directory,
            'parent' => $parent,
        ];
    }

    /**
     * @param  array<int, UploadedFile|null>  $files
     */
    public function upload(string $directory, array $files): void
    {
        $absolute = $this->absoluteDirectory($directory);

        foreach ($files as $file) {
            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                throw new RuntimeException('Ошибка загрузки файла');
            }

            $filename = $this->sanitizeUploadName($file->getClientOriginalName());
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            if (! in_array($ext, self::EXTENSIONS, true)) {
                throw new RuntimeException('Недопустимый тип файла');
            }

            $mime = (string) $file->getMimeType();
            $allowedMime = [
                'image/jpeg', 'image/pjpeg', 'image/png', 'image/x-png',
                'image/gif', 'image/webp',
            ];
            if (! in_array($mime, $allowedMime, true)) {
                throw new RuntimeException('Недопустимый тип файла');
            }

            $file->move($absolute, $filename);
        }
    }

    /**
     * Upload one image for WYSIWYG editor. Returns relative path from disk root (catalog/...).
     */
    public function uploadOne(string $directory, UploadedFile $file): string
    {
        $directory = $this->sanitizeDirectory($directory);

        try {
            $absolute = $this->absoluteDirectory($directory);
        } catch (Throwable) {
            $catalog = $this->catalogRoot();
            $absolute = $directory === ''
                ? $catalog
                : $catalog.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $directory);

            if (! is_dir($absolute) && ! mkdir($absolute, 0775, true) && ! is_dir($absolute)) {
                throw new RuntimeException('Cannot create upload directory');
            }

            $absolute = $this->assertInside($absolute, $this->realCatalogRoot());
        }

        if (! $file->isValid()) {
            throw new RuntimeException('Ошибка загрузки файла');
        }

        $filename = $this->sanitizeUploadName($file->getClientOriginalName());
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (! in_array($ext, self::EXTENSIONS, true)) {
            throw new RuntimeException('Недопустимый тип файла');
        }

        $mime = (string) $file->getMimeType();
        $allowedMime = [
            'image/jpeg', 'image/pjpeg', 'image/png', 'image/x-png',
            'image/gif', 'image/webp',
        ];
        if (! in_array($mime, $allowedMime, true)) {
            throw new RuntimeException('Недопустимый тип файла');
        }

        $base = pathinfo($filename, PATHINFO_FILENAME);
        $candidate = $filename;
        $i = 1;
        while (is_file($absolute.DIRECTORY_SEPARATOR.$candidate)) {
            $candidate = $base.'-'.$i.'.'.$ext;
            $i++;
        }

        $file->move($absolute, $candidate);

        return $this->relativeFromDisk($absolute.DIRECTORY_SEPARATOR.$candidate);
    }

    public function createFolder(string $directory, string $folder): void
    {
        $absolute = $this->absoluteDirectory($directory);
        $folder = trim(basename(html_entity_decode($folder, ENT_QUOTES, 'UTF-8')));
        $folder = preg_replace('/[^\p{L}\p{N}\-_ ]/u', '', $folder) ?? '';
        $folder = trim(preg_replace('/\s+/', '-', $folder) ?? '');

        if ($folder === '' || mb_strlen($folder) > 128) {
            throw new RuntimeException('Некорректное имя папки');
        }

        $path = $absolute.DIRECTORY_SEPARATOR.$folder;
        $this->assertInside($path, $this->realCatalogRoot());

        if (is_dir($path)) {
            throw new RuntimeException('Папка уже существует');
        }

        if (! mkdir($path, 0775, true) && ! is_dir($path)) {
            throw new RuntimeException('Не удалось создать папку');
        }
    }

    /**
     * @param  array<int, string>  $paths
     */
    public function delete(array $paths): void
    {
        $catalog = $this->realCatalogRoot();

        foreach ($paths as $path) {
            $path = $this->sanitizeDirectory(str_replace('\\', '/', (string) $path));
            if ($path === '' || $path === 'catalog') {
                throw new RuntimeException('Нельзя удалить корневую папку');
            }

            $absolute = $this->diskRoot().DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $path);
            if (! file_exists($absolute)) {
                continue;
            }

            $absolute = $this->assertInside($absolute, $catalog);

            if (is_file($absolute)) {
                @unlink($absolute);
                continue;
            }

            $this->deleteDirectory($absolute);
        }
    }

    public function publicUrl(string $relativeFromDisk): string
    {
        $relativeFromDisk = ltrim(str_replace('\\', '/', $relativeFromDisk), '/');

        if ($relativeFromDisk === '') {
            return $this->placeholderUrl();
        }

        if (preg_match('#^https?://#i', $relativeFromDisk)) {
            return $relativeFromDisk;
        }

        foreach (['storage/'.$relativeFromDisk, $relativeFromDisk] as $rel) {
            if (is_file(public_path($rel))) {
                return asset($rel);
            }
        }

        if (is_file($this->diskRoot().DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relativeFromDisk))) {
            return asset('storage/'.$relativeFromDisk);
        }

        return $this->placeholderUrl();
    }

    public function thumbUrl(string $relativeFromDisk, int $width = 100, int $height = 100): string
    {
        $relativeFromDisk = ltrim(str_replace('\\', '/', $relativeFromDisk), '/');
        $source = $this->diskRoot().DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relativeFromDisk);

        if (! is_file($source) || ! $this->isAllowedFilename($relativeFromDisk)) {
            return $this->placeholderUrl();
        }

        $cacheRel = 'cache/fm/'.$width.'x'.$height.'/'.preg_replace('/\.[^.]+$/', '.jpg', $relativeFromDisk);
        $cacheAbs = $this->diskRoot().DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $cacheRel);

        if (! is_file($cacheAbs) || filemtime($cacheAbs) < filemtime($source)) {
            try {
                $this->makeThumb($source, $cacheAbs, $width, $height);
            } catch (Throwable) {
                return $this->publicUrl($relativeFromDisk);
            }
        }

        if (is_file($cacheAbs)) {
            return asset('storage/'.$cacheRel);
        }

        return $this->publicUrl($relativeFromDisk);
    }

    private function makeThumb(string $source, string $dest, int $width, int $height): void
    {
        if (! function_exists('imagecreatetruecolor')) {
            return;
        }

        $info = @getimagesize($source);
        if (! $info) {
            return;
        }

        [$srcW, $srcH, $type] = $info;
        $create = match ($type) {
            IMAGETYPE_JPEG => 'imagecreatefromjpeg',
            IMAGETYPE_PNG => 'imagecreatefrompng',
            IMAGETYPE_GIF => 'imagecreatefromgif',
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? 'imagecreatefromwebp' : null,
            default => null,
        };

        if (! $create) {
            return;
        }

        $src = @$create($source);
        if (! $src) {
            return;
        }

        $ratio = min($width / max($srcW, 1), $height / max($srcH, 1));
        $dstW = max(1, (int) round($srcW * $ratio));
        $dstH = max(1, (int) round($srcH * $ratio));

        $dst = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($dst, 255, 255, 255);
        imagefilledrectangle($dst, 0, 0, $width, $height, $white);

        if (in_array($type, [IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP], true)) {
            imagealphablending($dst, true);
            imagesavealpha($dst, true);
        }

        $offsetX = (int) (($width - $dstW) / 2);
        $offsetY = (int) (($height - $dstH) / 2);
        imagecopyresampled($dst, $src, $offsetX, $offsetY, 0, 0, $dstW, $dstH, $srcW, $srcH);

        $dir = dirname($dest);
        if (! is_dir($dir) && ! @mkdir($dir, 0777, true) && ! is_dir($dir)) {
            return;
        }

        if (! is_writable($dir)) {
            @chmod($dir, 0777);
        }

        if (! is_writable($dir)) {
            imagedestroy($src);
            imagedestroy($dst);

            return;
        }

        @imagejpeg($dst, $dest, 82);
        imagedestroy($src);
        imagedestroy($dst);
    }

    private function sanitizeUploadName(string $name): string
    {
        $name = basename(html_entity_decode($name, ENT_QUOTES, 'UTF-8'));
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $base = pathinfo($name, PATHINFO_FILENAME);
        $base = Str::slug($base) ?: 'image';

        return $base.'.'.$ext;
    }

    private function isAllowedFilename(string $name): bool
    {
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        return in_array($ext, self::EXTENSIONS, true);
    }

    private function relativeFromDisk(string $absolute): string
    {
        $disk = str_replace('\\', '/', $this->realDiskRoot());
        $path = str_replace('\\', '/', $absolute);

        return ltrim(substr($path, strlen($disk)), '/');
    }

    private function realCatalogRoot(): string
    {
        $root = realpath($this->catalogRoot());
        if ($root === false) {
            throw new RuntimeException('Catalog directory is missing');
        }

        return $root;
    }

    private function realDiskRoot(): string
    {
        $root = realpath($this->diskRoot()) ?: $this->diskRoot();
        if (! is_dir($root)) {
            throw new RuntimeException('Image disk is missing');
        }

        return $root;
    }

    private function assertInside(string $path, string $root): string
    {
        $rootNorm = rtrim(str_replace('\\', '/', $root), '/');
        $real = realpath($path);
        $pathNorm = rtrim(str_replace('\\', '/', $real !== false ? $real : $path), '/');

        if ($pathNorm !== $rootNorm && ! str_starts_with($pathNorm, $rootNorm.'/')) {
            throw new RuntimeException('Invalid path');
        }

        return $real !== false ? $real : $path;
    }

    private function deleteDirectory(string $directory): void
    {
        $items = scandir($directory) ?: [];

        foreach ($items as $name) {
            if ($name === '.' || $name === '..') {
                continue;
            }

            $full = $directory.DIRECTORY_SEPARATOR.$name;
            if (is_dir($full)) {
                $this->deleteDirectory($full);
            } else {
                @unlink($full);
            }
        }

        @rmdir($directory);
    }
}
