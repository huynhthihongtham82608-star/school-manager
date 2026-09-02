<?php

namespace App\Services\Chatbot;

use App\Models\User;
use Illuminate\Support\Collection;

class SchoolChatbotService
{
    public function __construct(private readonly ChatbotOrchestrator $orchestrator)
    {
    }

    public function recentMessages(User $user, int $limit = 20): Collection
    {
        return $this->orchestrator->recentMessages($user, $limit);
    }

    public function historyPayload(User $user, int $limit = 30): array
    {
        return $this->orchestrator->historyPayload($user, $limit);
    }

    public function clearHistory(User $user): void
    {
        $this->orchestrator->clearHistory($user);
    }

    public function answer(User $user, string $question): array
    {
        return $this->orchestrator->answer($user, $question);
    }
}
