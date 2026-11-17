<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Receives images dropped into the rich text editor and returns the URL
 * to embed.
 */
class UploadController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,gif,webp', 'max:5120'],
        ]);

        $path = $request->file('file')->store('', 'uploads');

        if ($path === false) {
            throw new RuntimeException('The uploaded image could not be saved.');
        }

        return response()->json(['url' => Storage::disk('uploads')->url($path)]);
    }
}
