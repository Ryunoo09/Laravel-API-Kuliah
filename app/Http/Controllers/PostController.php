<?php

namespace App\Http\Controllers;

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
     * Display a listing of all posts.
     * GET /api/posts
     */
    public function index(Request $request): JsonResponse
    {
        $posts = $this->postService->getAllPosts($request->user());
        return response()->json($posts);
    }

    /**
     * Store a newly created post.
     * POST /api/posts
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:100',
            'status' => 'in:draft,published',
            'content' => 'required|string',
        ]);

        $post = $this->postService->createPost($validated, $request->user());

        return response()->json([
            'id' => $post->id,
            'title' => $post->title,
            'status' => $post->status,
            'content' => $post->content,
            'user_id' => $post->user_id,
            'link' => "/api/posts/{$post->id}",
        ], 201);
    }

    /**
     * Display the specified post.
     * GET /api/posts/{id}
     */
    public function show(Request $request, Post $post): JsonResponse
    {
        $post = $this->postService->getPost($post, $request->user());
        return response()->json($post);
    }

    /**
     * Update the specified post.
     * PUT/PATCH /api/posts/{id}
     */
    public function update(Request $request, Post $post): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:100',
            'status' => 'sometimes|in:draft,published',
            'content' => 'sometimes|string',
        ]);

        $updatedPost = $this->postService->updatePost($post, $validated, $request->user());

        return response()->json($updatedPost);
    }

    /**
     * Remove the specified post.
     * DELETE /api/posts/{id}
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
