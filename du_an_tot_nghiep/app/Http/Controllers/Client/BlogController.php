<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\{BlogPost, BlogCategory, BlogTag};
use Illuminate\Http\Request;

class BlogController extends Controller
{
    public function index(Request $r)
    {
        $q = BlogPost::with(['category', 'author', 'tags'])
            ->published()->orderByDesc('published_at');

        if ($r->filled('category')) {
            $cat = BlogCategory::where('slug', $r->category)->firstOrFail();
            $q->where('category_id', $cat->id);
        }
        if ($r->filled('tag')) {
            $tag = BlogTag::where('slug', $r->tag)->firstOrFail();
            $q->whereHas('tags', fn($qq) => $qq->where('blog_tags.id', $tag->id));
        }
        if ($r->filled('kw')) $q->where('title', 'like', '%' . $r->kw . '%');

        $posts = $q->paginate(12)->withQueryString();
        $categories = BlogCategory::orderBy('name')->get();
        $tags = BlogTag::orderBy('name')->get();

        return view('client.blog.index', compact('posts', 'categories', 'tags'));
    }

    public function show($slug)
    {
        $post = BlogPost::with(['category', 'author', 'tags'])
            ->published()->where('slug', $slug)->firstOrFail();
        $post->increment('views');
        return view('client.blog.show', compact('post'));
    }
}
