<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Receives a pronunciation recorded in the term editor and returns the
 * address to save with the term.
 *
 * Browsers record in different formats (WebM or Ogg with Opus, or MP4
 * with AAC), and some report WebM and MP4 audio as video, so both are
 * accepted.
 */
class PronunciationUploadController extends Controller
{
    private const array TYPES = [
        'audio/webm', 'video/webm', 'audio/ogg', 'video/ogg', 'audio/mp4', 'video/mp4',
        'audio/x-m4a', 'audio/aac', 'audio/mpeg', 'audio/wav', 'audio/x-wav',
    ];

    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimetypes:'.implode(',', self::TYPES), 'max:2048'],
        ], [
            'file.mimetypes' => 'The recording must be an audio file.',
            'file.max' => 'The recording is too long. Keep it to a few seconds.',
        ]);

        $path = $request->file('file')?->store('pronunciations', 'uploads');

        if (! is_string($path)) {
            throw new RuntimeException('The recording could not be saved.');
        }

        return response()->json(['url' => Storage::disk('uploads')->url($path)]);
    }
}
