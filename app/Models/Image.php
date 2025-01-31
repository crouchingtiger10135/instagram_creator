<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'file_path',
        'position',
        'caption',
        'instagram_media_id', // If using Instagram import
    ];

    /**
     * The attributes that should be cast to native types.
     */
    protected $casts = [
        'position' => 'integer',
    ];

    /**
     * Relationship: which user owns this image?
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Accessor for full URL (optional convenience).
     */
    public function getUrlAttribute()
    {
        return asset('storage/' . $this->file_path);
    }

    /**
     * Scope for only Instagram-imported images (optional).
     */
    public function scopeImportedFromInstagram($query)
    {
        return $query->whereNotNull('instagram_media_id');
    }
}
