<?php

declare(strict_types=1);

namespace ArtM\CustomerSurvey\Api;

interface SurveyEngineInterface
{
    /**
     * Return surveys eligible for current context.
     *
     * @param array<string, mixed> $context
     * @return array<int, array<string, mixed>>
     */
    public function getEligibleSurveys(array $context = []): array;

    /**
     * Track a survey impression.
     *
     * @param array<string, mixed> $payload
     * @return bool
     */
    public function trackImpression(array $payload = []): bool;

    /**
     * Track a survey close event.
     *
     * @param array<string, mixed> $payload
     * @return bool 
     */
    public function trackClosed(array $payload = []): bool;

    /**
     * Track popup open event.
     *
     * @param array<string, mixed> $payload
     * @return bool
     */
    public function trackPopupOpen(array $payload = []): bool;

    /**
     * Track a survey answered event.
     *
     * @param array<string, mixed> $payload
     * @return bool
     */
    public function trackAnswered(array $payload = []): bool;

    /**
     * Submit answers and persist response details.
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function submitAnswers(array $payload = []): array;
}
