<?php

namespace Gundo\ProductInfoAgent\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class ImageAiProviders implements ArrayInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 'dalle', 'label' => __('DALL-E')],
            ['value' => 'gemini', 'label' => __('Gemini')],
        ];
    }
} 