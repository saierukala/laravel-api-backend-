<?php

use App\Models\GalleryFolder;
use App\Models\GalleryImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    foreach ([
        'gallery.folders.view',
        'gallery.folders.edit',
        'gallery.images.create',
        'gallery.images.delete',
    ] as $permission) {
        Permission::findOrCreate($permission, 'web');
    }
});

function galleryUserWithPermissions(array $permissions): User
{
    $user = User::factory()->create();
    $user->givePermissionTo($permissions);

    return $user;
}

test('gallery folders render with cover images and image counts', function (): void {
    Storage::fake('public');

    $user = galleryUserWithPermissions(['gallery.folders.view']);
    $folder = GalleryFolder::create([
        'title' => 'Projects',
        'description' => 'Recent project photos',
    ]);
    $image = GalleryImage::create([
        'gallery_folder_id' => $folder->id,
        'filename' => 'cover.jpg',
        'original_name' => 'cover.jpg',
        'mime_type' => 'image/jpeg',
        'size' => 1200,
    ]);

    Storage::disk('public')->put(
        "gallery/{$folder->id}/{$image->filename}",
        'image-data',
    );

    $response = $this->actingAs($user)->get(route('gallery.folders.index'));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Gallery/Folders')
            ->has('folders', 1)
            ->where('folders.0.title', 'Projects')
            ->where('folders.0.images_count', 1)
            ->where(
                'folders.0.cover_image_url',
                Storage::disk('public')->url("gallery/{$folder->id}/{$image->filename}"),
            ));
});

test('gallery folder details render stored images', function (): void {
    Storage::fake('public');

    $user = galleryUserWithPermissions(['gallery.folders.view']);
    $folder = GalleryFolder::create([
        'title' => 'Site Visit',
        'description' => null,
    ]);

    GalleryImage::create([
        'gallery_folder_id' => $folder->id,
        'filename' => 'site.jpg',
        'original_name' => 'site-original.jpg',
        'mime_type' => 'image/jpeg',
        'size' => 2048,
        'width' => 640,
        'height' => 480,
    ]);

    $response = $this->actingAs($user)->get(route('gallery.folders.show', $folder));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Gallery/EditFolder')
            ->where('folder.title', 'Site Visit')
            ->has('folder.images', 1)
            ->where('folder.images.0.original_name', 'site-original.jpg')
            ->where('folder.images.0.width', 640)
            ->where('folder.images.0.height', 480));
});

test('multiple photos can be uploaded to a gallery folder', function (): void {
    Storage::fake('public');

    $user = galleryUserWithPermissions([
        'gallery.folders.view',
        'gallery.folders.edit',
        'gallery.images.create',
    ]);
    $folder = GalleryFolder::create([
        'title' => 'Uploads',
        'description' => null,
    ]);

    $response = $this->actingAs($user)->post(route('gallery.images.upload', $folder), [
        'images' => [
            UploadedFile::fake()->image('one.jpg', 120, 90),
            UploadedFile::fake()->image('two.png', 300, 200),
        ],
    ]);

    $response->assertRedirect(route('gallery.folders.show', $folder));

    $images = $folder->images()->orderBy('original_name')->get();

    expect($images)->toHaveCount(2)
        ->and($images->pluck('original_name')->all())->toBe(['one.jpg', 'two.png']);

    foreach ($images as $image) {
        Storage::disk('public')->assertExists("gallery/{$folder->id}/{$image->filename}");
    }
});

test('photo uploads require folder edit permission', function (): void {
    Storage::fake('public');

    $user = galleryUserWithPermissions([
        'gallery.folders.view',
        'gallery.images.create',
    ]);
    $folder = GalleryFolder::create([
        'title' => 'Uploads',
        'description' => null,
    ]);

    $this->actingAs($user)
        ->post(route('gallery.images.upload', $folder), [
            'images' => [
                UploadedFile::fake()->image('one.jpg', 120, 90),
            ],
        ])
        ->assertForbidden();

    expect($folder->images()->count())->toBe(0);
});

test('an image cannot be deleted through a different folder', function (): void {
    Storage::fake('public');

    $user = galleryUserWithPermissions([
        'gallery.folders.view',
        'gallery.folders.edit',
        'gallery.images.delete',
    ]);
    $folder = GalleryFolder::create([
        'title' => 'Correct Folder',
        'description' => null,
    ]);
    $otherFolder = GalleryFolder::create([
        'title' => 'Other Folder',
        'description' => null,
    ]);
    $image = GalleryImage::create([
        'gallery_folder_id' => $otherFolder->id,
        'filename' => 'other.jpg',
        'original_name' => 'other.jpg',
        'mime_type' => 'image/jpeg',
        'size' => 800,
    ]);

    Storage::disk('public')->put(
        "gallery/{$otherFolder->id}/{$image->filename}",
        'image-data',
    );

    $this
        ->actingAs($user)
        ->delete(route('gallery.images.destroy', [$folder, $image]))
        ->assertNotFound();

    expect($image->fresh())->not->toBeNull();
    Storage::disk('public')->assertExists("gallery/{$otherFolder->id}/{$image->filename}");
});

test('photo deletes require folder edit permission', function (): void {
    Storage::fake('public');

    $user = galleryUserWithPermissions([
        'gallery.folders.view',
        'gallery.images.delete',
    ]);
    $folder = GalleryFolder::create([
        'title' => 'Read Only Folder',
        'description' => null,
    ]);
    $image = GalleryImage::create([
        'gallery_folder_id' => $folder->id,
        'filename' => 'readonly.jpg',
        'original_name' => 'readonly.jpg',
        'mime_type' => 'image/jpeg',
        'size' => 800,
    ]);

    Storage::disk('public')->put(
        "gallery/{$folder->id}/{$image->filename}",
        'image-data',
    );

    $this
        ->actingAs($user)
        ->delete(route('gallery.images.destroy', [$folder, $image]))
        ->assertForbidden();

    expect($image->fresh())->not->toBeNull();
    Storage::disk('public')->assertExists("gallery/{$folder->id}/{$image->filename}");
});
