<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\UserResource;

class CommentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     * Ini setara dengan method transform() di Fractal Transformer.
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'comment'    => $this->comment,
            'post_id'    => $this->post_id,
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
            // Relasi: tampilkan data user pemilik comment
            'user'       => new UserResource($this->whenLoaded('user')),
        ];
    }
}
