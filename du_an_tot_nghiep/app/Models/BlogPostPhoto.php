<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

class BlogPostPhoto extends Model
{
    use Auditable;
    protected $fillable = ['post_id','image'];

    public function post()
    {
        return $this->belongsTo(BlogPost::class, 'post_id');
    }
}

?>