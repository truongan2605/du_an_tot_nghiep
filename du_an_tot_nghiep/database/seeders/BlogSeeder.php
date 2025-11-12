<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{BlogCategory, BlogTag, BlogPost, User};
use Illuminate\Support\Str;

class BlogSeeder extends Seeder
{
    public function run(): void
    {
        $cat = BlogCategory::firstOrCreate(['slug' => 'tin-tuc'], ['name' => 'Tin tức']);
        $tag = BlogTag::firstOrCreate(['slug' => 'hotel'], ['name' => 'Hotel']);
        $user = User::first();

        for ($i = 1; $i <= 6; $i++) {
            $p = BlogPost::create([
                'user_id' => $user?->id ?? 1,
                'category_id' => $cat->id,
                'title' => "Bài viết mẫu $i",
                'excerpt' => 'Đoạn tóm tắt ngắn…',
                'content' => "Nội dung mẫu cho bài viết $i.\nBạn có thể sửa trong admin.",
                'status' => 'published',
                'published_at' => now()->subDays($i),
            ]);
            $p->tags()->sync([$tag->id]);
        }
    }
}
