<?php

namespace Gundo\ProductInfoAgent\Api;

interface ChatFeedbackInterface
{
    /**
     * @param  int $chatId
     * @param  int $feedback
     * @return bool
     */
    public function submitFeedback(int $chatId, int $feedback): bool;
} 