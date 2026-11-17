<?php

namespace App\Actions\Site;

use App\Themes\ThemeSettings;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Reads the font families a font stylesheet (such as an Adobe Fonts kit)
 * declares in its @font-face rules, so themes can offer them.
 */
class ReadFontFamilies
{
    /**
     * The families, or null when the stylesheet could not be fetched.
     *
     * @return list<string>|null
     */
    public function handle(string $url): ?array
    {
        try {
            $css = Http::timeout(8)->accept('text/css')->get($url)->throw()->body();
        } catch (ConnectionException|RequestException $exception) {
            Log::warning('The site\'s font stylesheet could not be read.', ['url' => $url, 'error' => $exception->getMessage()]);

            return null;
        }

        return $this->families($css);
    }

    /**
     * @return list<string>
     */
    public function families(string $css): array
    {
        preg_match_all('/@font-face\s*\{[^}]*?font-family\s*:\s*["\']?([^;"\'}]+?)["\']?\s*[;}]/i', $css, $matches);

        $families = array_filter(
            array_map(trim(...), $matches[1]),
            fn (string $family): bool => preg_match(ThemeSettings::FAMILY, $family) === 1,
        );

        return array_values(array_unique($families));
    }
}
