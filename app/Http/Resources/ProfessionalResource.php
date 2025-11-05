<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfessionalResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'bio' => $this->bio,
            'skills' => $this->skills,
            'photo' => $this->photo ? asset('storage/' . $this->photo) : null,
            'reputation_score' => (float) $this->reputation_score,
            'reputation_badges' => $this->reputation_badges ?? [],
            'schedule' => $this->schedule ?? [],
            'total_reviews' => $this->total_reviews,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'user' => new UserResource($this->whenLoaded('user')),
        ];
    }
}

