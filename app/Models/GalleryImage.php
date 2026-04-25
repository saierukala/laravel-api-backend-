<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['gallery_folder_id', 'filename', 'original_name', 'mime_type', 'size', 'width', 'height'])]
class GalleryImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'gallery_folder_id',
        'filename',
        'original_name',
        'mime_type',
        'size',
        'width',
        'height',
    ];

    protected function casts(): array
    {
        return [];
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(GalleryFolder::class, 'gallery_folder_id');
    }
}