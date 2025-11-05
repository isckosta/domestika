<?php

namespace App\Policies;

use App\Models\ServiceRequest;
use App\Models\User;

class ServiceRequestPolicy
{
    /**
     * Determine if the user can view the service request.
     */
    public function view(User $user, ServiceRequest $serviceRequest): bool
    {
        // Contractors can view their own requests
        if ($serviceRequest->user_id === $user->id) {
            return true;
        }

        // Professionals can view requests where they are matched
        $professional = \App\Models\Professional::where('user_id', $user->id)->first();
        if ($professional) {
            $matchedProfessionals = $serviceRequest->matched_professionals ?? [];
            foreach ($matchedProfessionals as $match) {
                if ($match['professional_id'] === $professional->id) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Determine if the user can create service requests.
     */
    public function create(User $user): bool
    {
        // Only contractors can create requests
        // In a real scenario, you might check for a role or profile type
        // For now, we allow any authenticated user (assuming they are contractors)
        return true;
    }

    /**
     * Determine if the user can update the service request.
     */
    public function update(User $user, ServiceRequest $serviceRequest): bool
    {
        // Only the contractor who created it can update
        return $serviceRequest->user_id === $user->id;
    }

    /**
     * Determine if the user can cancel the service request.
     */
    public function cancel(User $user, ServiceRequest $serviceRequest): bool
    {
        // Only the contractor who created it can cancel
        return $serviceRequest->user_id === $user->id;
    }

    /**
     * Determine if the user can complete the service request.
     */
    public function complete(User $user, ServiceRequest $serviceRequest): bool
    {
        // Both contractor and professional can mark as completed
        if ($serviceRequest->user_id === $user->id) {
            return true;
        }

        // Check if professional has accepted response
        $professional = \App\Models\Professional::where('user_id', $user->id)->first();
        if ($professional) {
            return \App\Models\ProfessionalResponse::where('service_request_id', $serviceRequest->id)
                ->where('professional_id', $professional->id)
                ->where('status', 'accepted')
                ->exists();
        }

        return false;
    }

    /**
     * Determine if the user can respond to the service request.
     */
    public function respond(User $user, ServiceRequest $serviceRequest): bool
    {
        // Only professionals can respond
        $professional = \App\Models\Professional::where('user_id', $user->id)->first();
        if (!$professional) {
            return false;
        }

        // Check if professional is in matched list
        $matchedProfessionals = $serviceRequest->matched_professionals ?? [];
        foreach ($matchedProfessionals as $match) {
            if ($match['professional_id'] === $professional->id) {
                return true;
            }
        }

        return false;
    }
}

