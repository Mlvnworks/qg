<?php

use Pusher\Pusher;

class PusherService
{
    private ?PDO $pdo;
    private ?Pusher $client = null;
    private string $lastError = '';

    public function __construct(?PDO $pdo)
    {
        $this->pdo = $pdo;

        if (
            class_exists(Pusher::class)
            && $this->isConfigured()
        ) {
            $this->client = new Pusher(PUSHER_APP_KEY, PUSHER_APP_SECRET, PUSHER_APP_ID, [
                'cluster' => PUSHER_APP_CLUSTER,
                'useTLS' => true,
            ]);
        }
    }

    public function isConfigured(): bool
    {
        return $this->hasValue(PUSHER_APP_ID)
            && $this->hasValue(PUSHER_APP_KEY)
            && $this->hasValue(PUSHER_APP_SECRET)
            && $this->hasValue(PUSHER_APP_CLUSTER);
    }

    public function isReady(): bool
    {
        return $this->client instanceof Pusher;
    }

    public function getClientConfig(): array
    {
        if (!$this->isReady()) {
            return [];
        }

        return [
            'key' => (string) PUSHER_APP_KEY,
            'cluster' => (string) PUSHER_APP_CLUSTER,
        ];
    }

    public function getLastError(): string
    {
        return $this->lastError;
    }

    public function triggerQuizEvent(int $quizId, string $eventName, array $data = []): bool
    {
        $this->lastError = '';
        $payload = array_merge($data, [
            'quiz_id' => $quizId,
            'event_name' => $eventName,
            'occurred_at' => gmdate('c'),
        ]);
        $wasTriggered = false;

        if ($this->client instanceof Pusher) {
            try {
                $this->client->trigger('quiz-' . $quizId, $eventName, $payload);
                $wasTriggered = true;
            } catch (Throwable $err) {
                $this->lastError = 'Pusher request failed: ' . $err->getMessage();
                error_log('Questra Pusher error: ' . $err->getMessage());
            }
        } elseif ($this->isConfigured()) {
            $this->lastError = 'Pusher is configured, but the PHP client could not be initialized.';
        } else {
            $this->lastError = 'Pusher credentials are missing or incomplete.';
        }

        if ($this->pdo instanceof PDO) {
            $stmt = $this->pdo->prepare('
                INSERT INTO realtime_logs (quiz_id, user_id, event_name, event_data, created_at)
                VALUES (:quiz_id, :user_id, :event_name, :event_data, NOW())
            ');
            $stmt->execute([
                'quiz_id' => $quizId,
                'user_id' => $payload['actor_user_id'] ?? null,
                'event_name' => $eventName,
                'event_data' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            ]);
        }

        return $wasTriggered;
    }

    private function hasValue(?string $value): bool
    {
        return is_string($value) && trim($value) !== '';
    }
}
