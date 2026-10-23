<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Rekomendasi tindakan preventif rule-based per kategori kualitas udara.
 *
 * Teks dan sumber dibaca dari config/recommendations.php.
 */
class Recommendation
{
    /**
     * @return array{category: string, level: string, text: string, strategies: list<string>}
     */
    public static function forCategory(string $category): array
    {
        $rule = config("recommendations.rules.$category");

        if (! is_array($rule)) {
            throw new InvalidArgumentException("Kategori tidak dikenal: $category");
        }

        return ['category' => $category] + $rule;
    }

    /**
     * @return list<array{category: string, level: string, text: string, strategies: list<string>}>
     */
    public static function all(): array
    {
        return array_map(self::forCategory(...), config('air_quality.category_order'));
    }

    /**
     * @return list<array{label: string, url: string}>
     */
    public static function sources(): array
    {
        return array_values(config('recommendations.sources'));
    }
}
