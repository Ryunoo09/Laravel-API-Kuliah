<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Services\CommentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    protected $commentService;

    public function __construct(CommentService $commentService)
    {
        $this->commentService = $commentService;
    }

    /**
     * Display all comments belonging to the authenticated user (or all if admin).
     * GET /api/comments
     */
    public function index(Request $request): JsonResponse
    {
        $comments = $this->commentService->getAllComments($request->user());
        return response()->json($comments);
    }

    /**
     * Store a new comment.
     * POST /api/comments
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'post_id' => 'required|exists:posts,id',
            'comment' => 'required|string|max:250',
        ]);

        $comment = $this->commentService->createComment($validated, $request->user());

        return response()->json($comment->load('user'), 201);
    }

    /**
     * Display a specific comment.
     * GET /api/comments/{comment}
     */
    public function show(Request $request, Comment $comment): JsonResponse
    {
        $comment = $this->commentService->getComment($comment, $request->user());
        return response()->json($comment);
    }

    /**
     * Update a specific comment.
     * PUT/PATCH /api/comments/{comment}
     */
    public function update(Request $request, Comment $comment): JsonResponse
    {
        $validated = $request->validate([
            'comment' => 'sometimes|string|max:250',
        ]);

        $updatedComment = $this->commentService->updateComment($comment, $validated, $request->user());

        return response()->json($updatedComment);
    }

    /**
     * Remove a specific comment.
     * DELETE /api/comments/{comment}
     */
    public function destroy(Request $request, Comment $comment): JsonResponse
    {
        $commentId = $comment->id;
        
        $this->commentService->deleteComment($comment, $request->user());

        return response()->json([
            'id' => $commentId,
            'deleted' => true,
        ]);
    }
}
