<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceRequestResource extends JsonResource
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
            'category' => $this->category,
            'workload_size' => $this->workload_size,
            'frequency' => $this->frequency,
            'urgency' => $this->urgency,
            'status' => $this->status,
            'matched_professionals' => $this->matched_professionals,
            'description' => $this->description,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            'user' => new UserResource($this->whenLoaded('user')),
            'responses' => ProfessionalResponseResource::collection($this->whenLoaded('responses')),
        ];
    }
}

