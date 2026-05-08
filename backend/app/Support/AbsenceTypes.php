<?php

namespace App\Support;

final class AbsenceTypes
{
    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return config('dayflow.absence_types', []);
    }

    /**
     * @return list<string>
     */
    public static function slugs(): array
    {
        return array_keys(self::labels());
    }

    public static function label(string $slug): string
    {
        return self::labels()[$slug] ?? $slug;
    }

    /**
     * @return list<array{slug: string, label: string}>
     */
    public static function forApi(): array
    {
        $out = [];
        foreach (self::labels() as $slug => $label) {
            $out[] = ['slug' => $slug, 'label' => $label];
        }

        return $out;
    }
}
