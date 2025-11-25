<?php

namespace Gundo\ProductInfoAgent\Api;

interface VoiceInterface
{
    /**
     * Generate voice audio for text
     *
     * @param  string      $text
     * @param  int|null    $productId
     * @param  string|null $sessionId
     * @return array
     */
    public function generateVoice(string $text, int $productId = null, string $sessionId = null): array;
} 