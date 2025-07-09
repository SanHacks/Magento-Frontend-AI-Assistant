<?php

declare(strict_types=1);

namespace Gundo\ProductInfoAgent\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Gundo\ProductInfoAgent\Api\SuggestionInterface;

class AgentSuggestions implements ResolverInterface
{
    private SuggestionInterface $suggestion;

    public function __construct(SuggestionInterface $suggestion)
    {
        $this->suggestion = $suggestion;
    }

    /**
     * @inheritDoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null): array
    {
        if (!isset($args['productId'])) {
            return [];
        }

        return $this->suggestion->getSuggestions($args['productId']);
    }
} 