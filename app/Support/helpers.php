<?php

use App\Models\Setting;

if (! function_exists('qty')) {
    /**
     * Format a stock quantity for display.
     *
     * Decimal columns come back as "40.000" and floats as "-30", so the obvious
     * `rtrim(rtrim($n, '0'), '.')` is wrong: with no decimal point to stop it,
     * it eats real trailing zeros and turns 100 into 1 and -30 into -3.
     * Trailing zeros are only ever dropped after a decimal point.
     */
    function qty(float|int|string|null $value, int $decimals = 3): string
    {
        if ($value === null || $value === '') {
            return '0';
        }

        $formatted = number_format((float) $value, $decimals, '.', '');

        return str_contains($formatted, '.')
            ? rtrim(rtrim($formatted, '0'), '.')
            : $formatted;
    }
}

if (! function_exists('money')) {
    /**
     * Pence to a displayable pound figure.
     */
    function money(int|float|null $pence): string
    {
        return '£'.number_format(((int) $pence) / 100, 2);
    }
}

if (! function_exists('vat_breakdown')) {
    /**
     * The VAT inside a price, when the business is VAT registered.
     *
     * Midland quotes the public VAT-inclusive, so the VAT is carved out of the
     * total rather than added on top: what the customer pays never changes
     * when the setting is switched on, only what the paperwork shows.
     *
     * @return array{rate: int, net: int, vat: int}|null Pence, or null when not registered.
     */
    function vat_breakdown(int $grossPence): ?array
    {
        if (! Setting::get('vat_registered', false)) {
            return null;
        }

        $rate = (int) Setting::get('vat_percent', 20);
        $vat = (int) round($grossPence * $rate / (100 + $rate));

        return ['rate' => $rate, 'net' => $grossPence - $vat, 'vat' => $vat];
    }
}

if (! function_exists('catering_units')) {
    /**
     * The units the business counts in. Orders and quotes use the first four
     * the client asked for; stock also needs litres and boxes.
     *
     * @return array<string, string>
     */
    function catering_units(bool $forStock = false): array
    {
        $units = ['kg' => __('KG'), 'portion' => __('Portion'), 'pieces' => __('Pieces'), 'number' => __('Number')];

        return $forStock ? $units + ['litres' => __('Litres'), 'boxes' => __('Boxes')] : $units;
    }
}

if (! function_exists('unit_label')) {
    /**
     * The word to show for a stored unit. Anything typed before the fixed list
     * existed ("trays", "bags") is shown as it was written.
     */
    function unit_label(?string $unit): string
    {
        if ($unit === null || $unit === '') {
            return '';
        }

        return catering_units(true)[mb_strtolower($unit)] ?? $unit;
    }
}
