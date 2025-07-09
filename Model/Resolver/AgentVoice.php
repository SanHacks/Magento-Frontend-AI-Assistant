<?php

declare(strict_types=1);

namespace Gundo\ProductInfoAgent\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Gundo\ProductInfoAgent\Api\VoiceInterface;
use Gundo\ProductInfoAgent\Logger\Logger;

class AgentVoice implements ResolverInterface
{
    private VoiceInterface $voice;
    private Logger $logger;

    public function __construct(VoiceInterface $voice, Logger $logger)
    {
        $this->voice = $voice;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null): array
    {
        try {
            $input = $args['input'];
            $response = $this->voice->generateVoice(
                $input['text'],
                $input['productId'] ?? null,
                $input['sessionId'] ?? null
            );

            return [
                'success' => $response['success'],
                'audio_data' => $response['audio_data'] ?? null,
                'error_message' => $response['error'] ?? null,
                'from_cache' => $response['from_cache'] ?? false,
                'model' => $response['model'] ?? null
            ];
        } catch (\Exception $e) {
            $this->logger->critical($e);
            return [
                'success' => false,
                'audio_data' => null,
                'error_message' => 'An exception occurred: ' . $e->getMessage(),
                'from_cache' => false,
                'model' => null
            ];
        }
    }
} 