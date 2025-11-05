<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfessionalResponseResource extends JsonResource
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
            'service_request_id' => $this->service_request_id,
            'professional_id' => $this->professional_id,
            'status' => $this->status,
            'message' => $this->message,
            'responded_at' => $this->responded_at->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'professional' => new ProfessionalResource($this->whenLoaded('professional')),
        ];
    }
}

