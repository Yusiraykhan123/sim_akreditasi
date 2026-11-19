<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Element;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AccreditationElementsSeeder extends Seeder
{
    public function run(): void
    {
        self::seedCategories();
    }

    public static function seedCategories(?array $categoryCodes = null): void
    {
        $path = __DIR__.'/data/accreditation-elements.json';
        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new RuntimeException("Data elemen akreditasi tidak dapat dibaca: {$path}");
        }

        $data = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
        $categoryData = $categoryCodes === null
            ? $data
            : array_intersect_key($data, array_flip($categoryCodes));
        $codes = $categoryCodes ?? array_keys($data);
        $categories = Category::query()
            ->whereIn('code', $codes)
            ->get()
            ->keyBy('code');

        foreach ($codes as $code) {
            if (! array_key_exists($code, $categoryData)) {
                throw new RuntimeException("Data elemen untuk bab {$code} tidak ditemukan.");
            }

            if (! $categories->has($code)) {
                throw new RuntimeException("Bab {$code} tidak ditemukan. Jalankan DatabaseSeeder terlebih dahulu.");
            }
        }

        DB::transaction(function () use ($codes, $categoryData, $categories): void {
            foreach ($codes as $code) {
                $category = $categories->get($code);
                $existingElements = $category->elements()->get();
                $elementsByKey = $existingElements->keyBy(
                    fn (Element $element): string => $element->standard."\0".$element->ep,
                );
                $elementsByNormalizedKey = $existingElements->keyBy(
                    fn (Element $element): string => rtrim($element->standard, '.')."\0".$element->ep,
                );

                foreach ($categoryData[$code] as $entry) {
                    $key = $entry['standard']."\0".$entry['ep'];
                    $normalizedKey = rtrim($entry['standard'], '.')."\0".$entry['ep'];
                    $element = $elementsByKey->get($key)
                        ?? $elementsByNormalizedKey->get($normalizedKey)
                        ?? new Element;

                    $element->fill([
                        'category_id' => $category->id,
                        'standard' => $entry['standard'],
                        'ep' => $entry['ep'],
                        'content' => $entry['content'],
                        'sort_order' => $entry['sort_order'],
                    ]);
                    $element->save();
                }
            }
        });
    }
}
