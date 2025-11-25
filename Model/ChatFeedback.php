<?php

namespace Gundo\ProductInfoAgent\Model;

use Gundo\ProductInfoAgent\Api\ChatFeedbackInterface;
use Gundo\ProductInfoAgent\Model\ProductInfoAgentFactory;
use Gundo\ProductInfoAgent\Logger\Logger;

class ChatFeedback implements ChatFeedbackInterface
{
    /**
     * @var ProductInfoAgentFactory
     */
    private ProductInfoAgentFactory $productInfoAgentFactory;

    /**
     * @var Logger
     */
    private Logger $logger;

    /**
     * @param ProductInfoAgentFactory $productInfoAgentFactory
     * @param Logger                  $logger
     */
    public function __construct(
        ProductInfoAgentFactory $productInfoAgentFactory,
        Logger $logger
    ) {
        $this->productInfoAgentFactory = $productInfoAgentFactory;
        $this->logger = $logger;
    }

    /**
     * @param  int $chatId
     * @param  int $feedback
     * @return bool
     */
    public function submitFeedback(int $chatId, int $feedback): bool
    {
        try {
            $model = $this->productInfoAgentFactory->create()->load($chatId);
            if ($model->getId()) {
                $model->setFeedback($feedback);
                $model->save();
                return true;
            }
        } catch (\Exception $e) {
            $this->logger->error('Error submitting feedback: ' . $e->getMessage());
        }
        return false;
    }
} 