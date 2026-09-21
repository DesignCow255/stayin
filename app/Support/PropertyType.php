<?php

declare(strict_types=1);

namespace App\Support;

/**
 * The eight property types actually supported by `properties.property_type`.
 *
 * Single source of truth for every surface that lists, filters or labels a
 * property type — search filters, the discovery grid and the host editor all
 * read from here, so the enum can never drift out of sync again.
 */
final class PropertyType
{
    /**
     * @var array<string, array{label: string, plural: string, native: string|null, icon: string, blurb: string}>
     */
    private const TYPES = [
        'hotel' => [
            'label' => 'Hotel',
            'plural' => 'Hotels',
            'native' => null,
            'icon' => 'hotel',
            'blurb' => 'Staffed accommodation with daily housekeeping and front-desk service.',
        ],
        'lodge' => [
            'label' => 'Lodge',
            'plural' => 'Lodges',
            'native' => null,
            'icon' => 'trees',
            'blurb' => 'Low-rise, nature-led stays built around their landscape.',
        ],
        'guest_house' => [
            'label' => 'Guest house',
            'plural' => 'Guest houses',
            'native' => null,
            'icon' => 'house',
            'blurb' => 'Small-scale hospitality with a handful of rooms and personal hosting.',
        ],
        'homestay' => [
            'label' => 'Homestay',
            'plural' => 'Homestays',
            'native' => null,
            'icon' => 'users',
            'blurb' => 'Stay with your host in a lived-in home, breakfast often included.',
        ],
        'chumba_kimoja' => [
            'label' => 'Single room',
            'plural' => 'Single rooms',
            'native' => 'Chumba kimoja',
            'icon' => 'bed',
            'blurb' => 'One self-contained room — the most economical way to stay.',
        ],
        'villa' => [
            'label' => 'Villa',
            'plural' => 'Villas',
            'native' => null,
            'icon' => 'key',
            'blurb' => 'A private residence taken as a whole, usually with your own outdoor space.',
        ],
        'apartment' => [
            'label' => 'Apartment',
            'plural' => 'Apartments',
            'native' => null,
            'icon' => 'building',
            'blurb' => 'Self-catering living with a kitchen and your own entrance.',
        ],
        'serviced_apartment' => [
            'label' => 'Serviced apartment',
            'plural' => 'Serviced apartments',
            'native' => null,
            'icon' => 'bell-service',
            'blurb' => 'Apartment space with hotel-style housekeeping and services.',
        ],
    ];

    /**
     * @return array<int, string>
     */
    public static function all(): array
    {
        return array_keys(self::TYPES);
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::TYPES as $value => $type) {
            $options[$value] = $type['label'];
        }

        return $options;
    }

    public static function has(string $value): bool
    {
        return isset(self::TYPES[$value]);
    }

    public static function label(string $value): string
    {
        return self::TYPES[$value]['label'] ?? Icon::label($value);
    }

    public static function plural(string $value): string
    {
        return self::TYPES[$value]['plural'] ?? Icon::label($value);
    }

    public static function icon(string $value): string
    {
        return self::TYPES[$value]['icon'] ?? 'house';
    }

    public static function blurb(string $value): string
    {
        return self::TYPES[$value]['blurb'] ?? '';
    }

    public static function native(string $value): ?string
    {
        return self::TYPES[$value]['native'] ?? null;
    }
}
