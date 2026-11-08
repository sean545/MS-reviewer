<?php

namespace App\Http\Controllers;

use App\Models\Material;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams private lesson files after an authorization check; nothing is publicly linked.
 */
class MaterialFileController extends Controller
{
    /**
     * The PDF, inline for the reader or as a download with ?download=1.
     */
    public function show(Request $request, Material $material): StreamedResponse
    {
        Gate::authorize('view', $material);

        $disk = Storage::disk(Material::DISK);
        abort_unless($disk->exists($material->file_path), 404);

        return $disk->response(
            $material->file_path,
            $material->downloadName(),
            ['Content-Type' => 'application/pdf'],
            $request->boolean('download') ? 'attachment' : 'inline',
        );
    }

    public function thumbnail(Material $material): StreamedResponse
    {
        Gate::authorize('view', $material);

        $disk = Storage::disk(Material::DISK);
        abort_unless($material->thumbnail_path !== null && $disk->exists($material->thumbnail_path), 404);

        return $disk->response($material->thumbnail_path, headers: ['Cache-Control' => 'private, max-age=86400']);
    }
}
