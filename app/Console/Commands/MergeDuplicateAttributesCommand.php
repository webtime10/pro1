<?php

namespace App\Console\Commands;

use App\Models\Attribute;
use App\Models\AttributeDescription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class MergeDuplicateAttributesCommand extends Command
{
    protected $signature = 'attributes:merge-duplicates
                            {--dry-run : Только показать план, без записи в БД}';

    protected $description = 'Объединить атрибуты с одинаковым названием (без учёта регистра) в один attribute_id';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $groups = $this->duplicateGroups();

        if ($groups === []) {
            $this->info('Дубликатов не найдено.');

            return self::SUCCESS;
        }

        $this->info(($dryRun ? '[dry-run] ' : '').'Групп дубликатов: '.count($groups));

        if ($dryRun) {
            foreach ($groups as $group) {
                $this->line(sprintf(
                    '  «%s» → главный #%d, слить %s',
                    $group['name'],
                    $group['keeper'],
                    implode(', ', $group['duplicates'])
                ));
            }

            return self::SUCCESS;
        }

        $merged = 0;
        $deleted = 0;
        $bar = $this->output->createProgressBar(count($groups));
        $bar->start();

        try {
            DB::transaction(function () use ($groups, &$merged, &$deleted, $bar) {
                foreach ($groups as $group) {
                    $keeper = $group['keeper'];
                    $dupes = $group['duplicates'];
                    $allIds = array_values(array_unique(array_merge([$keeper], $dupes)));

                    $inList = implode(',', array_map('intval', $allIds));
                    $keeper = (int) $keeper;

                    DB::affectingStatement("
                        DELETE pa FROM product_attributes pa
                        INNER JOIN (
                            SELECT product_id, language_id,
                                   COALESCE(
                                       MAX(CASE WHEN attribute_id = {$keeper} THEN attribute_id END),
                                       MIN(attribute_id)
                                   ) AS keep_id
                            FROM product_attributes
                            WHERE attribute_id IN ({$inList})
                            GROUP BY product_id, language_id
                        ) k ON k.product_id = pa.product_id
                           AND k.language_id = pa.language_id
                        WHERE pa.attribute_id IN ({$inList})
                          AND pa.attribute_id <> k.keep_id
                    ");

                    $merged += DB::table('product_attributes')
                        ->whereIn('attribute_id', $dupes)
                        ->update(['attribute_id' => $keeper]);

                    $deleted += Attribute::query()->whereIn('id', $dupes)->delete();

                    $bar->advance();
                }
            });
        } catch (Throwable $e) {
            $bar->finish();
            $this->newLine();
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $bar->finish();
        $this->newLine();
        $this->info("Готово. Обновлено связей product_attribute: {$merged}. Удалено атрибутов: {$deleted}.");

        return self::SUCCESS;
    }

    /**
     * Связные компоненты: ID с одинаковым названием (и транзитивно через общее имя).
     *
     * @return list<array{name: string, keeper: int, duplicates: list<int>}>
     */
    private function duplicateGroups(): array
    {
        $rows = AttributeDescription::query()
            ->select('attribute_id', 'name')
            ->whereRaw("TRIM(name) <> ''")
            ->orderBy('attribute_id')
            ->get();

        $parent = [];
        $nameFor = [];

        $find = function (int $id) use (&$parent, &$find): int {
            if (($parent[$id] ?? $id) === $id) {
                return $id;
            }
            $parent[$id] = $find($parent[$id]);

            return $parent[$id];
        };
        $union = function (int $a, int $b) use (&$parent, $find): void {
            $ra = $find($a);
            $rb = $find($b);
            if ($ra === $rb) {
                return;
            }
            if ($ra < $rb) {
                $parent[$rb] = $ra;
            } else {
                $parent[$ra] = $rb;
            }
        };

        $byName = [];
        foreach ($rows as $row) {
            $id = (int) $row->attribute_id;
            $parent[$id] ??= $id;
            $key = mb_strtolower(trim((string) $row->name));
            if ($key === '') {
                continue;
            }
            $byName[$key]['label'] = trim((string) $row->name);
            $byName[$key]['ids'][] = $id;
        }

        foreach ($byName as $item) {
            $ids = array_values(array_unique($item['ids']));
            if (count($ids) < 2) {
                continue;
            }
            $first = $ids[0];
            foreach (array_slice($ids, 1) as $id) {
                $union($first, $id);
            }
        }

        $components = [];
        foreach (array_keys($parent) as $id) {
            $root = $find((int) $id);
            $components[$root][] = (int) $id;
        }

        $groups = [];
        foreach ($components as $ids) {
            $ids = array_values(array_unique($ids));
            sort($ids);
            if (count($ids) < 2) {
                continue;
            }

            $label = '';
            foreach ($byName as $item) {
                if (array_intersect($item['ids'], $ids)) {
                    $label = $item['label'];
                    if (in_array($ids[0], $item['ids'], true)) {
                        break;
                    }
                }
            }

            $groups[] = [
                'name' => $label,
                'keeper' => (int) $ids[0],
                'duplicates' => array_map('intval', array_slice($ids, 1)),
            ];
        }

        usort($groups, fn ($a, $b) => $a['keeper'] <=> $b['keeper']);

        return $groups;
    }
}
