<?php

namespace Gundo\ProductInfoAgent\Api;

interface ChatInterface
{
    /**
     * @param  string $message
     * @param  int    $productId
     * @param  int    $customerId
     * @return array
     */
    public function sendMessage(string $message,int $productId,int $customerId): array;
}
