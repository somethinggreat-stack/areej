<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Business rules the owner can change without a developer.
 *
 * Reads go through a single cached array rather than a query per lookup,
 * because settings are read on nearly every request.
 */
class Setting extends Model
{
    public const CACHE_KEY = 'mc-settings';

    protected $fillable = ['key', 'value', 'type', 'group'];

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    /** @return array<string, mixed> */
    public static function allValues(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, fn () => static::query()
            ->get()
            ->mapWithKeys(fn (Setting $row) => [$row->key => $row->castValue()])
            ->all());
    }

    public static function get(string $key, mixed $fallback = null): mixed
    {
        return static::allValues()[$key] ?? $fallback;
    }

    public static function put(string $key, mixed $value, string $type = 'string', string $group = 'general'): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => is_bool($value) ? ($value ? '1' : '0') : (string) $value, 'type' => $type, 'group' => $group]
        );
    }

    public function castValue(): mixed
    {
        return match ($this->type) {
            'int' => (int) $this->value,
            'float' => (float) $this->value,
            'bool' => (bool) $this->value,
            default => $this->value,
        };
    }
}
