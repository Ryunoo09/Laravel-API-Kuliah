<?php

namespace App\Http\Controllers;

use App\Http\Resources\CommentResource;
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
     * List All Comments
     *
     * Menampilkan semua comment milik user yang login. Admin dapat melihat semua comment.
     *
     * @group Comments
     * @authenticated
     *
     * @response 200 {
     *   "data": [
     *     {
     *       "id": 1,
     *       "comment": "Komentar pertama.",
     *       "post_id": 1,
     *       "created_at": "2026-04-20 10:00:00",
     *       "updated_at": "2026-04-20 10:00:00",
     *       "user": {
     *         "id": 1,
     *         "name": "Admin",
     *         "email": "admin@example.com",
     *         "role": "admin"
     *       }
     *     }
     *   ]
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $comments = $this->commentService->getAllComments($request->user());
        return CommentResource::collection($comments)->response();
    }

    /**
     * Create Comment
     *
     * Membuat comment baru pada post tertentu.
     *
     * @group Comments
     * @authenticated
     *
     * @bodyParam post_id integer required ID post yang akan dikomentari. Example: 1
     * @bodyParam comment string required Isi komentar, maksimal 250 karakter. Example: Artikel yang sangat informatif!
     *
     * @response 201 {
     *   "data": {
     *     "id": 1,
     *     "comment": "Artikel yang sangat informatif!",
     *     "post_id": 1,
     *     "created_at": "2026-04-20 10:00:00",
     *     "updated_at": "2026-04-20 10:00:00",
     *     "user": {
     *       "id": 1,
     *       "name": "Admin",
     *       "email": "admin@example.com",
     *       "role": "admin"
     *     }
     *   }
     * }
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'post_id' => 'required|exists:posts,id',
            'comment' => 'required|string|max:250',
        ]);

        $comment = $this->commentService->createComment($validated, $request->user());

        return (new CommentResource($comment->load('user')))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Show Comment
     *
     * Menampilkan detail satu comment berdasarkan ID.
     *
     * @group Comments
     * @authenticated
     *
     * @urlParam comment integer required ID dari comment. Example: 1
     *
     * @response 200 {
     *   "data": {
     *     "id": 1,
     *     "comment": "Komentar pertama.",
     *     "post_id": 1,
     *     "created_at": "2026-04-20 10:00:00",
     *     "updated_at": "2026-04-20 10:00:00"
     *   }
     * }
     */
    public function show(Request $request, Comment $comment): JsonResponse
    {
        $comment = $this->commentService->getComment($comment, $request->user());
        return (new CommentResource($comment))->response();
    }

    /**
     * Update Comment
     *
     * Mengubah isi comment. Hanya pemilik comment atau admin yang bisa mengubah.
     *
     * @group Comments
     * @authenticated
     *
     * @urlParam comment integer required ID dari comment. Example: 1
     * @bodyParam comment string Isi komentar baru. Example: Komentar yang sudah diperbarui.
     *
     * @response 200 {
     *   "data": {
     *     "id": 1,
     *     "comment": "Komentar yang sudah diperbarui.",
     *     "post_id": 1,
     *     "created_at": "2026-04-20 10:00:00",
     *     "updated_at": "2026-04-25 12:00:00"
     *   }
     * }
     */
    public function update(Request $request, Comment $comment): JsonResponse
    {
        $validated = $request->validate([
            'comment' => 'sometimes|string|max:250',
        ]);

        $updatedComment = $this->commentService->updateComment($comment, $validated, $request->user());

        return (new CommentResource($updatedComment))->response();
    }

    /**
     * Delete Comment
     *
     * Menghapus comment berdasarkan ID. Hanya pemilik comment atau admin yang bisa menghapus.
     *
     * @group Comments
     * @authenticated
     *
     * @urlParam comment integer required ID dari comment. Example: 1
     *
     * @response 200 {
     *   "id": 1,
     *   "deleted": true
     * }
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
