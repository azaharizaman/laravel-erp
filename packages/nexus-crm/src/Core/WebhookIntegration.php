<?php

declare(strict_types=1);

namespace Nexus\Crm\Core;

use Nexus\Crm\Models\CrmEntity;
use Nexus\Crm\Contracts\IntegrationContract;
use Illuminate\Support\Facades\Http;

/**
 * Webhook Integration
 *
 * Sends webhooks as part of CRM pipeline actions.
 */
class WebhookIntegration implements IntegrationContract
{
    /**
     * Execute webhook integration.
     */
    public function execute(CrmEntity $entity, array $config, array $context = []): void
    {
        $url = $config['url'] ?? '';
        $method = $config['method'] ?? 'POST';
        $headers = $config['headers'] ?? [];

        if (!$url) {
            throw new \InvalidArgumentException('Webhook URL is required');
        }

        $payload = [
            'entity' => $entity->toArray(),
            'config' => $config,
            'context' => $context,
            'timestamp' => now()->toISOString(),
        ];

        try {
            Http::withHeaders($headers)
                ->send($method, $url, ['json' => $payload]);
        } catch (\Exception $e) {
            \Log::error('CRM Webhook Integration Failed', [
                'entity_id' => $entity->id,
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Compensate webhook integration (no-op for webhooks).
     */
    public function compensate(CrmEntity $entity, array $config, array $context = []): void
    {
        // Webhooks don't need compensation
    }
}