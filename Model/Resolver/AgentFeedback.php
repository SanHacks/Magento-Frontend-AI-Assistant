<?php

declare(strict_types=1);

namespace Gundo\ProductInfoAgent\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Gundo\ProductInfoAgent\Api\ChatFeedbackInterface;
use Gundo\ProductInfoAgent\Logger\Logger;

class AgentFeedback implements ResolverInterface
{
    private ChatFeedbackInterface $chatFeedback;
    private Logger $logger;

    public function __construct(ChatFeedbackInterface $chatFeedback, Logger $logger)
    {
        $this->chatFeedback = $chatFeedback;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null): array
    {
        try {
            $input = $args['input'];
            $success = $this->chatFeedback->submitFeedback(
                $input['chatId'],
                $input['feedback']
            );

            return [
                'success' => $success,
                'error_message' => $success ? null : 'Failed to submit feedback'
            ];
        } catch (\Exception $e) {
            $this->logger->critical($e);
            return [
                'success' => false,
                'error_message' => 'An exception occurred: ' . $e->getMessage()
            ];
        }
    }
} 