<?php

namespace App\Http\Controllers\Admin\Blog;

use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index()
    {
        $items = BlogCategory::latest()->paginate(10);
        return view('admin.blog.categories.index', compact('items'));
    }
    public function create()
    {
        return view('admin.blog.categories.form', ['item' => new BlogCategory]);
    }
    public function store(Request $r)
    {
        $data = $r->validate(['name' => 'required|max:255', 'slug' => 'nullable|max:255|unique:blog_categories,slug']);
        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);
        BlogCategory::create($data);
        return redirect()->route('admin.blog.categories.index')->with('success', 'Đã tạo thành công');
    }
    public function edit(BlogCategory $category)
    {
        return view('admin.blog.categories.form', ['item' => $category]);
    }
    public function update(Request $r, BlogCategory $category)
    {
        $data = $r->validate(['name' => 'required|max:255', 'slug' => "nullable|max:255|unique:blog_categories,slug,{$category->id}"]);
        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);
        $category->update($data);
        return redirect()->route('admin.blog.categories.index')->with('success', 'Đã cập nhật thành công');
    }
    public function destroy(BlogCategory $category)
    {
        $category->delete();
        return back()->with('success', 'Đã xóa thành công');
    }
}
