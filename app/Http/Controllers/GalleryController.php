<?php

namespace App\Http\Controllers;

use App\Models\GalleryFolder;
use App\Models\GalleryImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Response;

class GalleryController extends Controller
{
    public function index(): Response
    {
        $folders = GalleryFolder::query()
            ->select(['id', 'title', 'description', 'created_at'])
            ->withCount('images')
            ->with('coverImage')
            ->orderBy('id')
            ->get()
            ->map(fn (GalleryFolder $folder) => [
                'id' => $folder->id,
                'title' => $folder->title,
                'description' => $folder->description,
                'created_at' => $folder->created_at?->toIso8601String(),
                'images_count' => (int) $folder->images_count,
                'cover_image_url' => $folder->coverImage
                    ? $this->imageUrl($folder, $folder->coverImage)
                    : null,
            ]);

        return inertia('Gallery/Folders', [
            'folders' => $folders,
        ]);
    }

    public function create(): Response
    {
        return inertia('Gallery/CreateFolder');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        GalleryFolder::create($validated);

        return to_route('gallery.folders.index');
    }

    public function show(GalleryFolder $folder): Response
    {
        return $this->renderFolderDetails($folder);
    }

    public function edit(GalleryFolder $folder): Response
    {
        return $this->renderFolderDetails($folder);
    }

    public function update(Request $request, GalleryFolder $folder): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $folder->update($validated);

        return to_route('gallery.folders.show', $folder);
    }

    public function destroy(GalleryFolder $folder): RedirectResponse
    {
        foreach ($folder->images as $image) {
            Storage::disk('public')->delete($this->imagePath($folder, $image));
        }

        Storage::disk('public')->deleteDirectory("gallery/{$folder->id}");
        $folder->delete();

        return to_route('gallery.folders.index');
    }

    public function uploadImages(Request $request, GalleryFolder $folder): RedirectResponse
    {
        $request->validate([
            'images' => ['required', 'array', 'min:1'],
            'images.*' => ['required', 'image', 'max:10240'],
        ]);

        foreach ($request->file('images') as $imageFile) {
            $filename = sprintf(
                '%s_%s.%s',
                now()->format('YmdHis'),
                uniqid('', true),
                $imageFile->getClientOriginalExtension(),
            );

            $imageFile->storeAs("gallery/{$folder->id}", $filename, 'public');

            $image = GalleryImage::create([
                'gallery_folder_id' => $folder->id,
                'filename' => $filename,
                'original_name' => $imageFile->getClientOriginalName(),
                'mime_type' => $imageFile->getMimeType(),
                'size' => $imageFile->getSize(),
            ]);

            $dimensions = @getimagesize($imageFile->getRealPath());

            if ($dimensions !== false) {
                $image->update([
                    'width' => $dimensions[0] ?? null,
                    'height' => $dimensions[1] ?? null,
                ]);
            }
        }

        return to_route('gallery.folders.show', $folder);
    }

    public function destroyImage(GalleryFolder $folder, GalleryImage $image): RedirectResponse
    {
        abort_unless($image->gallery_folder_id === $folder->id, 404);

        Storage::disk('public')->delete($this->imagePath($folder, $image));
        $image->delete();

        return to_route('gallery.folders.show', $folder);
    }

    private function renderFolderDetails(GalleryFolder $folder): Response
    {
        $folder->load(['images' => function ($query) {
            $query->orderBy('id');
        }]);

        return inertia('Gallery/EditFolder', [
            'folder' => [
                'id' => $folder->id,
                'title' => $folder->title,
                'description' => $folder->description,
                'images' => $folder->images->map(fn (GalleryImage $image) => [
                    'id' => $image->id,
                    'filename' => $image->filename,
                    'original_name' => $image->original_name,
                    'mime_type' => $image->mime_type,
                    'size' => $image->size,
                    'width' => $image->width,
                    'height' => $image->height,
                    'url' => $this->imageUrl($folder, $image),
                    'created_at' => $image->created_at?->toIso8601String(),
                ])->all(),
            ],
        ]);
    }

    private function imagePath(GalleryFolder $folder, GalleryImage $image): string
    {
        return "gallery/{$folder->id}/{$image->filename}";
    }

    private function imageUrl(GalleryFolder $folder, GalleryImage $image): string
    {
        return Storage::disk('public')->url($this->imagePath($folder, $image));
    }
}
