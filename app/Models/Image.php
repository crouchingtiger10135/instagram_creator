<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'file_path',
        'position',
        'caption',
        'instagram_media_id', // <-- Added for tracking Instagram media
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'position' => 'integer',
    ];

    /**
     * Get the user that owns the image.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Accessor to get the full URL of the image.
     *
     * @return string
     */
    public function getUrlAttribute()
    {
        return asset('storage/' . $this->file_path);
    }

    /**
     * Scope a query to only include Instagram imported images.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeImportedFromInstagram($query)
    {
        return $query->whereNotNull('instagram_media_id');
    }
}
