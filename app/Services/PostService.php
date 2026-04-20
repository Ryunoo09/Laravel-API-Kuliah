<?php

namespace App\Services;

use App\Models\Post;

class PostService
{
    /**
     * Get all posts depending on user role
     */
    public function getAllPosts($user)
    {
        $query = Post::with('user');
        
        if (!$user->isAdmin()) {
            $query->where('user_id', $user->id);
        }
        
        return $query->get();
    }

    /**
     * Create a new post
     */
    public function createPost(array $data, $user)
    {
        $data['user_id'] = $user->id;
        return Post::create($data);
    }

    /**
     * Get a specific post, validating ownership
     */
    public function getPost(Post $post, $user)
    {
        if (!$user->isAdmin() && $post->user_id !== $user->id) {
            abort(403, 'Unauthorized access.');
        }

        return $post->load('user', 'comments');
    }

    /**
     * Update a specific post, validating ownership
     */
    public function updatePost(Post $post, array $data, $user)
    {
        if (!$user->isAdmin() && $post->user_id !== $user->id) {
            abort(403, 'Unauthorized access.');
        }

        $post->update($data);
        return $post;
    }

    /**
     * Delete a specific post, only allowed for administrators
     */
    public function deletePost(Post $post, $user)
    {
        if (!$user->isAdmin()) {
            abort(403, 'Only admins can delete posts.');
        }

        $post->delete();
        return true;
    }
}
