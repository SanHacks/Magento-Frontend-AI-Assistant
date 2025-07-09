<?php

namespace Gundo\ProductInfoAgent\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

class FeedbackOptions implements OptionSourceInterface
{
    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => '1', 'label' => __('Positive')],
            ['value' => '-1', 'label' => __('Negative')],
            ['value' => '0', 'label' => __('No Feedback')],
        ];
    }
} 