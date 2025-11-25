<?php

namespace Gundo\ProductInfoAgent\Api;

interface SuggestionInterface
{
    /**
     * Get suggested questions for a product.
     *
     * @param  int $productId
     * @return string[]
     */
    public function getSuggestions(int $productId): array;
} 