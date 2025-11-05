<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    public function test_health_endpoint_returns_ok(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'timestamp',
                'services' => [
                    'database',
                    'redis',
                    'queue',
                ],
                'version',
            ]);
    }

    public function test_metrics_endpoint_returns_prometheus_format(): void
    {
        $response = $this->get('/api/v1/metrics');

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'text/plain; version=0.0.4');
    }
}
