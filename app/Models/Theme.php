<?php

namespace App\Models;

use App\Support\CurrentSite;
use App\Themes\ThemeDefinition;
use App\Themes\ThemeSettings;
use Carbon\CarbonImmutable;
use Database\Factories\ThemeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A custom theme made by an administrator. Built-in themes are files in
 * resources/themes; both share the settings shape in ThemeSettings.
 *
 * @property int $id
 * @property string $key
 * @property string $name
 * @property string|null $based_on
 * @property array<mixed> $settings
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['key', 'name', 'based_on', 'settings'])]
class Theme extends Model
{
    /** @use HasFactory<ThemeFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'settings' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => app(CurrentSite::class)->forget());
        static::deleted(fn () => app(CurrentSite::class)->forget());
    }

    public function definition(): ThemeDefinition
    {
        return new ThemeDefinition(
            key: $this->key,
            name: $this->name,
            builtIn: false,
            basedOn: $this->based_on,
            settings: ThemeSettings::normalise($this->settings),
        );
    }
}
