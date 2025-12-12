<?php

namespace App\Http\Requests\Blog;

use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
{
  public function authorize(): bool { return true; }
  public function rules(): array {
    return [
      'title' => 'required|string|max:255',
      'slug'  => 'nullable|string|max:255|unique:blog_posts,slug',
      'category_id' => 'nullable|exists:blog_categories,id',
      'status' => 'required|in:draft,published',
      'cover_image' => 'nullable|image|max:2048',
      'excerpt' => 'nullable|string|max:500',
      'content' => 'nullable|string',
      'tags' => 'array',
      'tags.*' => 'integer|exists:blog_tags,id',
      'meta_title' => 'nullable|string|max:500',
      'meta_description' => 'nullable|string|max:500',
    ];
  }
}
