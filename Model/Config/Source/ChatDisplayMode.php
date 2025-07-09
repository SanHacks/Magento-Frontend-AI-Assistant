<?php

namespace Gundo\ProductInfoAgent\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class ChatDisplayMode implements ArrayInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 'embedded', 'label' => __('Embedded (default, modern panel)')],
            ['value' => 'stick', 'label' => __('Stick (fixed to viewport)')],
            ['value' => 'popout', 'label' => __('Popout (always opens as popout)')],
            ['value' => 'nochange', 'label' => __('No Change (remembers last user state)')],
        ];
    }
} 