<?php

namespace App\Services;

use App\Models\Professional;
use App\Models\ServiceRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MatchingEngineService
{
    // Weight factors for hybrid scoring (without geolocation)
    private const WEIGHT_VECTOR = 0.5;
    private const WEIGHT_REPUTATION = 0.3;
    private const WEIGHT_AVAILABILITY = 0.1;
    private const WEIGHT_SKILLS = 0.1;

    /**
     * Find and rank compatible professionals for a service request.
     */
    public function findMatchingProfessionals(ServiceRequest $request, int $limit = 5): array
    {
        $professionals = Professional::where('is_active', true)
            ->whereNotNull('embedding_profile')
            ->get();

        $matches = [];

        foreach ($professionals as $professional) {
            $score = $this->calculateHybridScore($request, $professional);

            if ($score > 0) {
                $matches[] = [
                    'professional' => $professional,
                    'score' => $score,
                    'scores' => $this->getDetailedScores($request, $professional),
                ];
            }
        }

        // Sort by score descending
        usort($matches, fn($a, $b) => $b['score'] <=> $a['score']);

        // Return top N matches
        return array_slice($matches, 0, $limit);
    }

    /**
     * Calculate hybrid compatibility score.
     */
    private function calculateHybridScore(ServiceRequest $request, Professional $professional): float
    {
        $vectorScore = $this->calculateVectorScore($request, $professional);
        $reputationScore = $this->calculateReputationScore($professional);
        $availabilityScore = $this->calculateAvailabilityScore($request, $professional);
        $skillScore = $this->calculateSkillScore($request, $professional);

        $matchScore = (
            ($vectorScore * self::WEIGHT_VECTOR) +
            ($reputationScore * self::WEIGHT_REPUTATION) +
            ($availabilityScore * self::WEIGHT_AVAILABILITY) +
            ($skillScore * self::WEIGHT_SKILLS)
        );

        return round($matchScore, 4);
    }

    /**
     * Calculate semantic similarity score using PGVector cosine distance.
     */
    private function calculateVectorScore(ServiceRequest $request, Professional $professional): float
    {
        if (!$request->embedding_request || !$professional->embedding_profile) {
            return 0.0;
        }

        try {
            // Use PostgreSQL cosine distance operator <=>
            $result = DB::selectOne(
                "SELECT 1 - (embedding_request <=> embedding_profile) as similarity
                 FROM service_requests sr, professionals p
                 WHERE sr.id = ? AND p.id = ?",
                [$request->id, $professional->id]
            );

            return max(0.0, min(1.0, (float) ($result->similarity ?? 0.0)));
        } catch (\Exception $e) {
            Log::error('Vector score calculation error', [
                'error' => $e->getMessage(),
            ]);

            return 0.0;
        }
    }

    /**
     * Calculate reputation score (normalized 0-1).
     */
    private function calculateReputationScore(Professional $professional): float
    {
        return (float) $professional->reputation_score;
    }

    /**
     * Calculate availability score based on frequency alignment.
     */
    private function calculateAvailabilityScore(ServiceRequest $request, Professional $professional): float
    {
        $schedule = $professional->schedule ?? [];
        $requestFrequency = $request->frequency;

        // Simple matching logic
        if (empty($schedule)) {
            return 0.5; // Default score if no schedule defined
        }

        // Check if professional's schedule supports the requested frequency
        $supportedFrequencies = $schedule['frequencies'] ?? ['once', 'weekly', 'biweekly', 'monthly'];

        if (in_array($requestFrequency, $supportedFrequencies)) {
            return 1.0;
        }

        return 0.5; // Partial match
    }

    /**
     * Calculate skill compatibility score.
     */
    private function calculateSkillScore(ServiceRequest $request, Professional $professional): float
    {
        $professionalSkills = $professional->skills ?? [];
        $category = $request->category;

        if (empty($professionalSkills)) {
            return 0.5; // Default score
        }

        // Direct category match
        if (in_array($category, $professionalSkills)) {
            return 1.0;
        }

        // Partial match based on related skills
        $relatedSkills = $this->getRelatedSkills($category);
        $matches = count(array_intersect($relatedSkills, $professionalSkills));

        return min(1.0, $matches / max(1, count($relatedSkills)));
    }

    /**
     * Get related skills for a category.
     */
    private function getRelatedSkills(string $category): array
    {
        $skillMap = [
            'cleaning' => ['cleaning', 'housekeeping', 'deep_cleaning'],
            'cooking' => ['cooking', 'meal_prep', 'baking'],
            'laundry' => ['laundry', 'ironing', 'clothing_care'],
            'babysitting' => ['babysitting', 'childcare', 'tutoring'],
            'gardening' => ['gardening', 'landscaping', 'plant_care'],
        ];

        return $skillMap[$category] ?? [$category];
    }

    /**
     * Get detailed scores for debugging/transparency.
     */
    private function getDetailedScores(ServiceRequest $request, Professional $professional): array
    {
        return [
            'vector' => $this->calculateVectorScore($request, $professional),
            'reputation' => $this->calculateReputationScore($professional),
            'availability' => $this->calculateAvailabilityScore($request, $professional),
            'skills' => $this->calculateSkillScore($request, $professional),
        ];
    }
}

