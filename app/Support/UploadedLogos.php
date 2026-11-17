<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Logos uploaded for the site or a theme, kept on the `uploads` disk in a
 * folder of their own. Logos are stored and shown by their public path.
 */
class UploadedLogos
{
    /** Validation rules for a logo upload. SVG is refused: it can carry script. */
    public const array RULES = ['nullable', 'file', 'mimes:png,jpg,jpeg,gif,webp', 'mimetypes:image/png,image/jpeg,image/gif,image/webp', 'max:2048'];

    /** @var array<string, string> */
    public const array MESSAGES = [
        'logo.mimes' => 'The logo must be a PNG, JPEG, GIF or WebP image.',
        'logo.mimetypes' => 'The logo must be a PNG, JPEG, GIF or WebP image.',
        'logo.max' => 'The logo must be no larger than 2 MB.',
    ];

    /**
     * Store an upload and return its public path.
     */
    public static function store(UploadedFile $file, string $folder): string
    {
        $path = $file->store($folder, 'uploads');

        if ($path === false) {
            throw new RuntimeException('The uploaded logo could not be saved.');
        }

        return Storage::disk('uploads')->url($path);
    }

    /**
     * Delete a logo, if it is one that was uploaded to this folder. Paths
     * elsewhere (such as a built-in theme's logo) are left alone.
     */
    public static function delete(?string $logo, string $folder): void
    {
        $prefix = rtrim(Storage::disk('uploads')->url($folder), '/').'/';

        if ($logo !== null && Str::startsWith($logo, $prefix)) {
            Storage::disk('uploads')->delete($folder.'/'.Str::after($logo, $prefix));
        }
    }
}
