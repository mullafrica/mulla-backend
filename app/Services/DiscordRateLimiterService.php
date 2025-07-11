<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use App\Jobs\ProcessDiscordBatch;

class DiscordRateLimiterService
{
    private const BATCH_KEY = 'discord_batch_messages';

    private function getBatchSize(): int
    {
        return config('services.discord.rate_limit.batch_size', 10);
    }

    private function getBatchTimeoutSeconds(): int
    {
        return config('services.discord.rate_limit.batch_timeout_seconds', 5);
    }

    public function queueMessage(array $messageData): void
    {
        $batchMessages = Cache::get(self::BATCH_KEY, []);
        $batchMessages[] = [
            'message' => $messageData['message'] ?? '',
            'details' => $messageData['details'] ?? null,
            'timestamp' => now()->toISOString()
        ];

        Cache::put(self::BATCH_KEY, $batchMessages, now()->addMinutes(10));

        if (count($batchMessages) >= $this->getBatchSize() || $this->shouldFlushBatch()) {
            $this->processBatch();
        } else {
            $this->scheduleBatchFlush();
        }
    }

    private function shouldFlushBatch(): bool
    {
        $batchMessages = Cache::get(self::BATCH_KEY, []);
        if (empty($batchMessages)) {
            return false;
        }

        $oldestMessage = collect($batchMessages)->min('timestamp');
        return now()->diffInSeconds($oldestMessage) >= $this->getBatchTimeoutSeconds();
    }

    private function scheduleBatchFlush(): void
    {
        if (!Cache::has('discord_batch_scheduled')) {
            Cache::put('discord_batch_scheduled', true, $this->getBatchTimeoutSeconds());
            ProcessDiscordBatch::dispatch()->delay(now()->addSeconds($this->getBatchTimeoutSeconds()));
        }
    }

    public function processBatch(): void
    {
        $batchMessages = Cache::get(self::BATCH_KEY, []);
        if (empty($batchMessages)) {
            return;
        }

        Cache::forget(self::BATCH_KEY);
        Cache::forget('discord_batch_scheduled');

        $this->sendBatchToDiscord($batchMessages);
    }


    private function sendBatchToDiscord(array $messages): void
    {
        try {
            $webhookUrl = $this->getWebhookUrl();
            if (!$webhookUrl) {
                Log::error('Discord webhook URL not configured');
                return;
            }

            if (count($messages) === 1) {
                $this->sendSingleMessage($webhookUrl, $messages[0]);
            } else {
                $this->sendBatchMessage($webhookUrl, $messages);
            }

        } catch (\Exception $e) {
            Log::error('Failed to send Discord batch', [
                'error' => $e->getMessage(),
                'messages_count' => count($messages)
            ]);
        }
    }

    private function sendSingleMessage(string $webhookUrl, array $message): void
    {
        $payload = [];

        if (!empty($message['details'])) {
            $payload['embeds'] = [[
                'title' => $message['message'],
                'color' => 0x3498db,
                'fields' => $this->formatDetailsAsFields($message['details']),
                'timestamp' => $message['timestamp']
            ]];
        } else {
            $payload['content'] = $message['message'];
        }

        $response = Http::timeout(10)->post($webhookUrl, $payload);

        if (!$response->successful()) {
            throw new \Exception("Discord webhook failed: " . $response->body(), $response->status());
        }
    }

    private function sendBatchMessage(string $webhookUrl, array $messages): void
    {
        $embeds = [];
        $contentMessages = [];

        foreach ($messages as $message) {
            if (!empty($message['details'])) {
                $embeds[] = [
                    'title' => $message['message'],
                    'color' => 0x3498db,
                    'fields' => $this->formatDetailsAsFields($message['details']),
                    'timestamp' => $message['timestamp']
                ];
            } else {
                $contentMessages[] = $message['message'];
            }
        }

        $payload = [];

        if (!empty($embeds)) {
            $payload['embeds'] = array_slice($embeds, 0, 10); // Discord limit
        }

        if (!empty($contentMessages)) {
            $content = "**Batched Messages:**\n" . implode("\n", array_slice($contentMessages, 0, 5));
            $payload['content'] = substr($content, 0, 2000); // Discord limit
        }

        $response = Http::timeout(10)->post($webhookUrl, $payload);

        if (!$response->successful()) {
            throw new \Exception("Discord webhook failed: " . $response->body(), $response->status());
        }
    }

    private function formatDetailsAsFields(array $details): array
    {
        $fields = [];
        foreach ($details as $key => $value) {
            $fields[] = [
                'name' => ucfirst(str_replace('_', ' ', $key)),
                'value' => (string) $value,
                'inline' => true
            ];
        }
        return $fields;
    }

    private function getWebhookUrl(): ?string
    {
        $webhookUrl = config('services.discord.webhook_url');
        if (env('APP_ENV') !== 'production') {
            $webhookUrl = config('services.discord.webhook_url_dev');
        }
        return $webhookUrl;
    }


    public function getBatchStatus(): array
    {
        return [
            'batch_queue_size' => count(Cache::get(self::BATCH_KEY, [])),
            'batch_size' => $this->getBatchSize(),
            'batch_timeout_seconds' => $this->getBatchTimeoutSeconds()
        ];
    }
}