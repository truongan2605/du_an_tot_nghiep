<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BlogCategory extends Model
{
    //
    use SoftDeletes;
    protected $fillable = ['name','slug'];
    public function posts(){ return $this->hasMany(BlogPost::class,'category_id'); }
}
