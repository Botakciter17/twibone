<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Campaign extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'slug',
        'description',
        'frame_path',
        'frame_width',
        'frame_height',
        'caption',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'frame_width' => 'integer',
        'frame_height' => 'integer',
    ];

    /**
     * Append accessors to array/JSON form.
     *
     * @var list<string>
     */
    protected $appends = [
        'frame_url',
    ];

    /**
     * Get public URL for the frame.
     */
    public function getFrameUrlAttribute(): ?string
    {
        if (!$this->frame_path) {
            return null;
        }

        if (str_starts_with($this->frame_path, 'http://') || str_starts_with($this->frame_path, 'https://')) {
            return $this->frame_path;
        }

        return '/storage/' . ltrim($this->frame_path, '/');
    }
}
