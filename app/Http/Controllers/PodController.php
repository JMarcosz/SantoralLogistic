<?php

namespace App\Http\Controllers;

use App\Models\Pod;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PodController extends Controller
{
    /**
     * Display the POD image.
     */
    public function showImage(Pod $pod): StreamedResponse|Response
    {
        // Check if POD has an image
        if (!$pod->image_path) {
            abort(404, 'No se encontró la imagen del POD.');
        }

        $disk = config('filesystems.default', 'local');

        // Check if file exists
        if (!Storage::disk($disk)->exists($pod->image_path)) {
            abort(404, 'El archivo de imagen no existe.');
        }

        // Get mime type
        $mimeType = Storage::disk($disk)->mimeType($pod->image_path);

        // Stream the file
        return Storage::disk($disk)->response($pod->image_path, null, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
