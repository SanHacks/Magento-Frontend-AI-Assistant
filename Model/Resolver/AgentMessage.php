<?php

declare(strict_types=1);

namespace Gundo\ProductInfoAgent\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Gundo\ProductInfoAgent\Model\Chat as ChatModel;
use Gundo\ProductInfoAgent\Logger\Logger;

class AgentMessage implements ResolverInterface
{
    private ChatModel $chat;
    private Logger $logger;

    public function __construct(ChatModel $chat, Logger $logger)
    {
        $this->chat = $chat;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null): array
    {
        try {
            $input = $args['input'];
            $response = $this->chat->sendMessage(
                $input['message'],
                $input['productId'] ?? null,
                $input['customerId'] ?? null,
                $input['sessionId'] ?? null
            );

            $firstResponse = $response[0] ?? [];

            if (empty($firstResponse) || isset($firstResponse['error'])) {
                 $this->logger->error('GraphQL AgentMessage Error:', $response);
                 return [
                    'message' => 'Sorry, there was an error processing your request.',
                    'chat_id' => null,
                    'model' => 'error_handler'
                ];
            }

            return [
                'message' => $firstResponse['message'],
                'chat_id' => $firstResponse['chat_id'],
                'model' => $firstResponse['model']
            ];
        } catch (\Exception $e) {
            $this->logger->critical($e);
            return [
                'message' => 'An exception occurred: ' . $e->getMessage(),
                'chat_id' => null,
                'model' => 'exception_handler'
            ];
        }
    }
} 