<?php

namespace App\Services;

use App\Models\Comment;

class CommentService
{
    /**
     * Get all comments depending on user role
     */
    public function getAllComments($user)
    {
        $query = Comment::with('user');
        
        if (!$user->isAdmin()) {
            $query->where('user_id', $user->id);
        }
        
        return $query->get();
    }

    /**
     * Create a new comment
     */
    public function createComment(array $data, $user)
    {
        $data['user_id'] = $user->id;
        return Comment::create($data);
    }

    /**
     * Get a specific comment, validating ownership
     */
    public function getComment(Comment $comment, $user)
    {
        if (!$user->isAdmin() && $comment->user_id !== $user->id) {
            abort(403, 'Unauthorized access.');
        }

        return $comment->load('user', 'post');
    }

    /**
     * Update a comment, validating ownership
     */
    public function updateComment(Comment $comment, array $data, $user)
    {
        if (!$user->isAdmin() && $comment->user_id !== $user->id) {
            abort(403, 'Unauthorized access.');
        }

        $comment->update($data);
        return $comment;
    }

    /**
     * Delete a specific comment, only allowed for administrators
     */
    public function deleteComment(Comment $comment, $user)
    {
        if (!$user->isAdmin()) {
            abort(403, 'Only admins can delete comments.');
        }

        $comment->delete();
        return true;
    }
}
