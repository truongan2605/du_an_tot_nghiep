<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlogPostPhoto extends Model
{
    protected $fillable = ['post_id','image'];

    public function post()
    {
        return $this->belongsTo(BlogPost::class, 'post_id');
    }
}

?>