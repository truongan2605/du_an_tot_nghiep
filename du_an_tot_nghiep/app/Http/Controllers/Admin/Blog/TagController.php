<?php

namespace App\Http\Controllers\Admin\Blog;

use App\Http\Controllers\Controller;
use App\Models\BlogTag;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TagController extends Controller
{
    public function index()
    {
        $items = BlogTag::latest()->paginate(10);
        return view('admin.blog.tags.index', compact('items'));
    }
    public function create()
    {
        return view('admin.blog.tags.form', ['item' => new BlogTag]);
    }
    public function store(Request $r)
    {
        $data = $r->validate(['name' => 'required|max:255', 'slug' => 'nullable|max:255|unique:blog_tags,slug']);
        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);
        BlogTag::create($data);
        return redirect()->route('admin.blog.tags.index')->with('success', 'Đã tạo');
    }
    public function edit(BlogTag $tag)
    {
        return view('admin.blog.tags.form', ['item' => $tag]);
    }
    public function update(Request $r, BlogTag $tag)
    {
        $data = $r->validate(['name' => 'required|max:255', 'slug' => "nullable|max:255|unique:blog_tags,slug,{$tag->id}"]);
        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);
        $tag->update($data);
        return redirect()->route('admin.blog.tags.index')->with('success', 'Đã cập nhật');
    }
    public function destroy(BlogTag $tag)
    {
        $tag->delete();
        return back()->with('success', 'Đã xóa');
    }
}
