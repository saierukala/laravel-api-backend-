<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['title', 'description'])]
class GalleryFolder extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'description'];

    protected function casts(): array
    {
        return [];
    }

    public function images(): HasMany
    {
        return $this->hasMany(GalleryImage::class, 'gallery_folder_id');
    }

    public function coverImage(): HasOne
    {
        return $this->hasOne(GalleryImage::class, 'gallery_folder_id')->oldestOfMany();
    }
}
