<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Article extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $hidden = ['featured_image_path'];

    protected $appends = ['featured_image_url'];

    public function getFeaturedImageUrlAttribute(): ?string
    {
        return $this->featured_image_path ? '/blog/'.$this->slug.'/image?v='.substr(hash('sha256', $this->featured_image_path), 0, 12) : null;
    }

    protected static function booted(): void
    {
        static::creating(function ($article) {
            if (! $article->slug) {
                $base = Str::slug($article->title) ?: 'article';
                $slug = $base;
                for ($i = 2; static::where('slug', $slug)->exists(); $i++) {
                    $slug = $base.'-'.$i;
                }
                $article->slug = $slug;
            }
        });
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published')->whereDate('published_at', '<=', today('Africa/Nairobi'));
    }

    protected function casts(): array
    {
        return ['is_car_feature' => 'boolean', 'is_demo_feature' => 'boolean', 'feature_year' => 'integer', 'views_count' => 'integer', 'published_at' => 'date', 'reviewed_at' => 'date'];
    }
}
