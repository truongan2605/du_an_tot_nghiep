<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class BlogPost extends Model
{
    //
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'category_id',
        'title',
        'slug',
        'cover_image',
        'excerpt',
        'content',
        'status',
        'published_at',
        'views',
        'meta_title',
        'meta_description'
    ];

    protected $casts = ['published_at' => 'datetime'];

    protected static function booted()
    {
        static::creating(function ($post) {
            $post->slug = $post->slug ?: Str::slug($post->title . '-' . Str::random(5));
            if ($post->status === 'published' && !$post->published_at) $post->published_at = now();
        });
        static::updating(function ($post) {
            if ($post->isDirty('status') && $post->status === 'published' && !$post->published_at) $post->published_at = now();
        });
    }

    public function category()
    {
        return $this->belongsTo(BlogCategory::class, 'category_id');
    }
    public function tags()
    {
        return $this->belongsToMany(BlogTag::class, 'blog_post_tag', 'post_id', 'tag_id');
    }
    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function scopePublished($q)
    {
        return $q->where('status', 'published');
    }
    public function photoAlbums()
    {
        return $this->hasMany(BlogPostPhoto::class, 'post_id');
    }

}
