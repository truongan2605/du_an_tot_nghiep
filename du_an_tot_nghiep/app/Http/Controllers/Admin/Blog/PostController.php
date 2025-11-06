<?php

namespace App\Http\Controllers\Admin\Blog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Blog\StorePostRequest;
use App\Http\Requests\Blog\UpdatePostRequest;
use App\Models\{BlogPost, BlogCategory, BlogTag};
// [NEW]
use App\Models\BlogPostPhoto; // model ảnh phụ

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class PostController extends Controller
{
    public function index(Request $r)
    {
        $q = BlogPost::with(['category','author'])->latest('updated_at');
        if ($r->filled('status')) $q->where('status', $r->status);
        if ($r->filled('kw'))     $q->where('title', 'like', '%'.$r->kw.'%');
        $posts = $q->paginate(10)->withQueryString();
        return view('admin.blog.posts.index', compact('posts'));
    }

    public function create()
    {
        return view('admin.blog.posts.form', [
            // [NEW] load sẵn quan hệ ảnh để form hiển thị khi edit
            'post'       => (new BlogPost())->load('photoAlbums'),
            'categories' => BlogCategory::orderBy('name')->get(),
            'tags'       => BlogTag::orderBy('name')->get(),
        ]);
    }

    public function store(StorePostRequest $req)
    {
        $data = $req->validated();

        // Không dùng guard 'admin' để tránh lỗi "guard not defined"
        $data['user_id'] = Auth::id()
            ?? auth('web')->id()
            ?? (int) User::query()->value('id'); // fallback user đầu tiên

        if ($req->hasFile('cover_image')) {
            $data['cover_image'] = $req->file('cover_image')->store('blog', 'public');
        }

        $tagIds = BlogTag::whereIn('id', $data['tags'] ?? [])->pluck('id')->all();

        try {
            DB::transaction(function () use ($data, $tagIds, $req) { // [NEW] truyền $req
                $post = BlogPost::create($data);
                $post->tags()->sync($tagIds);

                // [NEW] lưu album ảnh (nhiều ảnh)
                $this->saveAlbumPhotos($post, $req);
            });
        } catch (\Throwable $e) {
            Log::error('Create BlogPost failed', ['message' => $e->getMessage()]);
            return back()->withInput()->with('error', 'Không tạo được bài viết. Vui lòng thử lại.');
        }

        return redirect()->route('admin.blog.posts.index')->with('success', 'Đã tạo bài viết thành công!');
    }

    public function update(UpdatePostRequest $req, BlogPost $post)
    {
        $data = $req->validated();

        if ($req->hasFile('cover_image')) {
            if ($post->cover_image && Storage::disk('public')->exists($post->cover_image)) {
                Storage::disk('public')->delete($post->cover_image);
            }
            $data['cover_image'] = $req->file('cover_image')->store('blog', 'public');
        }

        DB::transaction(function () use ($post, $data, $req) { // [NEW] truyền $req
            $post->update($data);
            $post->tags()->sync($data['tags'] ?? []);

            // [NEW] thêm ảnh mới vào album (không xóa ảnh cũ)
            $this->saveAlbumPhotos($post, $req);
        });

        return redirect()->route('admin.blog.posts.index')->with('success','Đã cập nhật thành công bài viết');
    }

    public function edit(BlogPost $post)
    {
        return view('admin.blog.posts.form', [
            'post'       => $post->load(['tags','photoAlbums']), // [NEW]
            'categories' => BlogCategory::orderBy('name')->get(),
            'tags'       => BlogTag::orderBy('name')->get(),
        ]);
    }

    // // Upload anh tren noi dung bai viet:
    // public function uploadContentImage(Request $request)
    // {
    //     $request->validate([
    //         'file' => 'required|image|mimes:jpg,jpeg,png,webp,gif|max:5120',
    //     ]);
    //     $path = $request->file('file')->store('blog_inline', 'public');
    //     return response()->json(['location' => asset('storage/' . $path)]);
    // }

    public function destroy(BlogPost $post)
    {
        $post->delete();
        return back()->with('success','Đã chuyển vào thùng rác');
    }

    public function trash()
    {
        $posts = BlogPost::onlyTrashed()->latest('deleted_at')->paginate(10);
        return view('admin.blog.posts.trash', compact('posts'));
    }

    public function restore($id)
    {
        BlogPost::onlyTrashed()->findOrFail($id)->restore();
        return back()->with('success','Đã khôi phục');
    }

    public function forceDelete($id)
    {
        $p = BlogPost::onlyTrashed()->findOrFail($id);
        if ($p->cover_image) Storage::disk('public')->delete($p->cover_image);

        // [NEW] xóa file ảnh phụ khi xóa vĩnh viễn
        foreach ($p->photoAlbums as $photo) {
            Storage::disk('public')->delete($photo->image);
        }

        $p->forceDelete();
        return back()->with('success','Đã xóa vĩnh viễn');
    }

    // =======================
    // [NEW] Helpers & actions
    // =======================

    /**
     * Lưu nhiều ảnh album gửi từ input name="photo_albums[]"
     */
    private function saveAlbumPhotos(BlogPost $post, Request $req): void
    {
        if (!$req->hasFile('photo_albums')) return;

        $req->validate([
            'photo_albums.*' => 'image|mimes:jpg,jpeg,png,webp,gif|max:4096',
        ]);

        foreach ($req->file('photo_albums') as $file) {
            if (!$file) continue;
            $path = $file->store('blog', 'public');
            $post->photoAlbums()->create([
                'image' => $path,
            ]);
        }
    }

    /**
     * Xóa 1 ảnh khỏi album
     */
    // public function deletePhoto(BlogPostPhoto $photo)
    // {
    //     Storage::disk('public')->delete($photo->image);
    //     $photo->delete();
    //     return back()->with('success', 'Đã xóa ảnh');
    // }

    public function deletePhoto($photoId)
{
    $photo = BlogPostPhoto::findOrFail($photoId);

    // Xóa file trong storage
    if (Storage::exists('public/' . $photo->image)) {
        Storage::delete('public/' . $photo->image);
    }

    // Xóa bản ghi trong database
    $photo->delete();

    // Trả về trang edit mà không lỗi
    return back()->with('success', 'Đã xóa ảnh thành công!');
}

}
