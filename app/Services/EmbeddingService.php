<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EmbeddingService
{
    private string $provider;
    private ?string $apiKey;
    private ?string $apiUrl;
    private ?string $model;
    private int $dimensions;

    public function __construct()
    {
        $this->provider = config('services.embeddings.provider', 'local');
        $this->apiKey = config('services.embeddings.api_key') ?: null;
        $this->apiUrl = config('services.embeddings.api_url') ?: null;
        $this->model = config('services.embeddings.model') ?: null;
        $this->dimensions = (int) config('services.embeddings.dimensions', 768);
    }

    /**
     * Generate embedding vector for a text.
     * Returns a vector array with the specified dimensions.
     * 
     * Supports multiple providers:
     * - 'local': Returns a placeholder vector (for development/testing)
     * - 'openai': Uses OpenAI API
     * - 'huggingface': Uses Hugging Face Inference API
     * - 'custom': Uses custom API endpoint
     */
    public function generateEmbedding(string $text): ?array
    {
        return match ($this->provider) {
            'local' => $this->generateLocalEmbedding($text),
            'openai' => $this->generateOpenAIEmbedding($text),
            'huggingface' => $this->generateHuggingFaceEmbedding($text),
            'custom' => $this->generateCustomEmbedding($text),
            default => $this->generateLocalEmbedding($text), // Fallback to local
        };
    }

    /**
     * Generate a placeholder/local embedding vector.
     * For development/testing purposes - generates a deterministic vector based on text hash.
     */
    private function generateLocalEmbedding(string $text): array
    {
        // Generate a deterministic vector based on text hash
        // This is a placeholder implementation for development
        $hash = md5($text);
        $vector = [];

        for ($i = 0; $i < $this->dimensions; $i++) {
            // Generate pseudo-random but deterministic values between -1 and 1
            $seed = hexdec(substr($hash, ($i * 2) % 32, 2));
            $vector[] = (($seed / 255) * 2) - 1; // Normalize to -1..1
        }

        return $vector;
    }

    /**
     * Generate embedding using OpenAI API.
     */
    private function generateOpenAIEmbedding(string $text): ?array
    {
        if (empty($this->apiKey)) {
            Log::warning('OpenAI API key not configured, falling back to local embedding');
            return $this->generateLocalEmbedding($text);
        }

        try {
            $apiUrl = $this->apiUrl ?: 'https://api.openai.com/v1/embeddings';
            $model = $this->model ?: 'text-embedding-3-small';

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post($apiUrl, [
                'model' => $model,
                'input' => $text,
                'dimensions' => $this->dimensions,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['data'][0]['embedding'] ?? null;
            }

            Log::warning('OpenAI embedding generation failed', [
                'status' => $response->status(),
                'text_length' => strlen($text),
            ]);

            return $this->generateLocalEmbedding($text); // Fallback
        } catch (\Exception $e) {
            Log::error('OpenAI embedding generation error', [
                'error' => $e->getMessage(),
            ]);

            return $this->generateLocalEmbedding($text); // Fallback
        }
    }

    /**
     * Generate embedding using Hugging Face Inference API.
     */
    private function generateHuggingFaceEmbedding(string $text): ?array
    {
        if (empty($this->apiKey)) {
            Log::warning('Hugging Face API key not configured, falling back to local embedding');
            return $this->generateLocalEmbedding($text);
        }

        try {
            $model = $this->model ?: 'sentence-transformers/all-MiniLM-L6-v2';
            $apiUrl = $this->apiUrl ?: "https://api-inference.huggingface.co/pipeline/feature-extraction/{$model}";

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post($apiUrl, [
                'inputs' => $text,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                // Hugging Face returns array directly or nested array
                $embedding = is_array($data[0] ?? null) ? $data[0] : $data;

                // Resize to target dimensions if needed
                if (count($embedding) !== $this->dimensions) {
                    return $this->resizeVector($embedding, $this->dimensions);
                }

                return $embedding;
            }

            Log::warning('Hugging Face embedding generation failed', [
                'status' => $response->status(),
                'text_length' => strlen($text),
            ]);

            return $this->generateLocalEmbedding($text); // Fallback
        } catch (\Exception $e) {
            Log::error('Hugging Face embedding generation error', [
                'error' => $e->getMessage(),
            ]);

            return $this->generateLocalEmbedding($text); // Fallback
        }
    }

    /**
     * Generate embedding using custom API endpoint.
     */
    private function generateCustomEmbedding(string $text): ?array
    {
        if (empty($this->apiUrl)) {
            Log::warning('Custom API URL not configured, falling back to local embedding');
            return $this->generateLocalEmbedding($text);
        }

        try {
            $headers = ['Content-Type' => 'application/json'];
            if ($this->apiKey) {
                $headers['Authorization'] = 'Bearer ' . $this->apiKey;
            }

            $payload = ['text' => $text];
            if ($this->dimensions) {
                $payload['dimensions'] = $this->dimensions;
            }

            $response = Http::withHeaders($headers)
                ->timeout(30)
                ->post($this->apiUrl, $payload);

            if ($response->successful()) {
                $data = $response->json();
                // Expecting response format: {"embedding": [...]} or direct array
                return $data['embedding'] ?? ($data['data']['embedding'] ?? $data);
            }

            Log::warning('Custom API embedding generation failed', [
                'status' => $response->status(),
            ]);

            return $this->generateLocalEmbedding($text); // Fallback
        } catch (\Exception $e) {
            Log::error('Custom API embedding generation error', [
                'error' => $e->getMessage(),
            ]);

            return $this->generateLocalEmbedding($text); // Fallback
        }
    }

    /**
     * Resize vector to target dimensions (simple interpolation).
     */
    private function resizeVector(array $vector, int $targetDimensions): array
    {
        $currentSize = count($vector);
        if ($currentSize === $targetDimensions) {
            return $vector;
        }

        $resized = [];
        $ratio = $currentSize / $targetDimensions;

        for ($i = 0; $i < $targetDimensions; $i++) {
            $srcIndex = (int) ($i * $ratio);
            $resized[] = $vector[$srcIndex] ?? 0;
        }

        return $resized;
    }

    /**
     * Generate embedding text from service request data.
     */
    public function generateRequestText(array $data): string
    {
        $parts = [
            "Service category: {$data['category']}",
            "Workload size: {$data['workload_size']}",
            "Frequency: {$data['frequency']}",
            "Urgency: {$data['urgency']}",
        ];

        if (!empty($data['description'])) {
            $parts[] = "Description: {$data['description']}";
        }

        return implode('. ', $parts);
    }

    /**
     * Generate embedding text from professional profile data.
     */
    public function generateProfileText(array $data): string
    {
        $parts = [];

        if (!empty($data['bio'])) {
            $parts[] = "Bio: {$data['bio']}";
        }

        if (!empty($data['skills']) && is_array($data['skills'])) {
            $parts[] = "Skills: " . implode(', ', $data['skills']);
        }

        return implode('. ', $parts);
    }
}

