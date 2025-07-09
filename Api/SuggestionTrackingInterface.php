<?php

namespace Gundo\ProductInfoAgent\Api;

interface SuggestionTrackingInterface
{
    /**
     * Track viewed suggestions for a user/session
     *
     * @param int $productId
     * @param int[] $suggestionIds
     * @param int|null $customerId
     * @param string|null $sessionId
     * @return bool
     */
    public function trackViewedSuggestions(int $productId, array $suggestionIds, ?int $customerId = null, ?string $sessionId = null): bool;

    /**
     * Get unviewed suggestions for a user/session
     *
     * @param int $productId
     * @param int|null $customerId
     * @param string|null $sessionId
     * @return array
     */
    public function getUnviewedSuggestions(int $productId, ?int $customerId = null, ?string $sessionId = null): array;

    /**
     * Reset viewed suggestions for a user/session
     *
     * @param int $productId
     * @param int|null $customerId
     * @param string|null $sessionId
     * @return bool
     */
    public function resetViewedSuggestions(int $productId, ?int $customerId = null, ?string $sessionId = null): bool;
} 