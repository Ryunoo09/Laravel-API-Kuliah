<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     * Ini setara dengan method transform() di Fractal Transformer.
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'title'      => $this->title,
            'status'     => $this->status,
            'content'    => $this->content,
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
            // Relasi: tampilkan data user pemilik post (nested resource)
            'user'       => new UserResource($this->whenLoaded('user')),
            // Relasi: tampilkan daftar comment (nested collection)
            'comments'   => CommentResource::collection($this->whenLoaded('comments')),
            // Link ke detail post
            'link'       => "/api/v1/posts/{$this->id}",
        ];
    }
}
