<?php

namespace App\Http\Controllers;

use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Services\PostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostController extends Controller
{
    protected $postService;

    public function __construct(PostService $postService)
    {
        $this->postService = $postService;
    }

    /**
     * List All Posts
     *
     * Menampilkan semua post milik user yang login. Admin dapat melihat semua post.
     *
     * @group Posts
     * @authenticated
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "title": "Post Pertama",
     *       "status": "published",
     *       "content": "Isi dari post pertama.",
     *       "created_at": "2026-04-20 10:00:00",
     *       "updated_at": "2026-04-20 10:00:00",
     *       "link": "/api/v1/posts/1"
     *     }
     *   ]
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $posts = $this->postService->getAllPosts($request->user());
        return PostResource::collection($posts)->response();
    }

    /**
     * Create Post
     *
     * Membuat post baru. Post akan otomatis dimiliki oleh user yang sedang login.
     *
     * @group Posts
     * @authenticated
     *
     * @bodyParam title string required Judul post, maksimal 100 karakter. Example: Belajar Laravel 12
     * @bodyParam content string required Isi/konten post. Example: Laravel 12 adalah versi terbaru dari framework PHP Laravel.
     * @bodyParam status string Status post: draft atau published. Default: draft. Example: published
     *
     * @response 201 {
     *   "data": {
     *     "id": 1,
     *     "title": "Belajar Laravel 12",
     *     "status": "published",
     *     "content": "Laravel 12 adalah versi terbaru dari framework PHP Laravel.",
     *     "created_at": "2026-04-20 10:00:00",
     *     "updated_at": "2026-04-20 10:00:00",
     *     "link": "/api/v1/posts/1"
     *   }
     * }
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:100',
            'status' => 'in:draft,published',
            'content' => 'required|string',
        ]);

        $post = $this->postService->createPost($validated, $request->user());

        return (new PostResource($post))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Show Post
     *
     * Menampilkan detail satu post berdasarkan ID.
     *
     * @group Posts
     * @authenticated
     *
     * @urlParam post integer required ID dari post. Example: 1
     *
     * @response 200 {
     *   "data": {
     *     "id": 1,
     *     "title": "Post Pertama",
     *     "status": "published",
     *     "content": "Isi dari post pertama.",
     *     "created_at": "2026-04-20 10:00:00",
     *     "updated_at": "2026-04-20 10:00:00",
     *     "link": "/api/v1/posts/1"
     *   }
     * }
     */
    public function show(Request $request, Post $post): JsonResponse
    {
        $post = $this->postService->getPost($post, $request->user());
        return (new PostResource($post))->response();
    }

    /**
     * Update Post
     *
     * Mengubah data post yang sudah ada. Hanya pemilik post atau admin yang bisa mengubah.
     *
     * @group Posts
     * @authenticated
     *
     * @urlParam post integer required ID dari post. Example: 1
     * @bodyParam title string Judul post baru. Example: Judul Diperbarui
     * @bodyParam content string Konten post baru. Example: Konten yang sudah diperbarui.
     * @bodyParam status string Status post: draft atau published. Example: published
     *
     * @response 200 {
     *   "data": {
     *     "id": 1,
     *     "title": "Judul Diperbarui",
     *     "status": "published",
     *     "content": "Konten yang sudah diperbarui.",
     *     "created_at": "2026-04-20 10:00:00",
     *     "updated_at": "2026-04-25 12:00:00",
     *     "link": "/api/v1/posts/1"
     *   }
     * }
     */
    public function update(Request $request, Post $post): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:100',
            'status' => 'sometimes|in:draft,published',
            'content' => 'sometimes|string',
        ]);

        $updatedPost = $this->postService->updatePost($post, $validated, $request->user());

        return (new PostResource($updatedPost))->response();
    }

    /**
     * Delete Post
     *
     * Menghapus post berdasarkan ID. Hanya pemilik post atau admin yang bisa menghapus.
     *
     * @group Posts
     * @authenticated
     *
     * @urlParam post integer required ID dari post. Example: 1
     *
     * @response 200 {
     *   "id": 1,
     *   "deleted": true
     * }
     */
    public function destroy(Request $request, Post $post): JsonResponse
    {
        $postId = $post->id;
        
        $this->postService->deletePost($post, $request->user());

        return response()->json([
            'id' => $postId,
            'deleted' => true,
        ]);
    }
}
