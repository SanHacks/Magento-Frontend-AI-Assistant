<?php
declare(strict_types=1);

namespace Gundo\ProductInfoAgent\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Stock Rules source model for admin configuration
 */
class StockRules implements OptionSourceInterface
{
    /**
     * Return array of options as value-label pairs
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'all', 'label' => __('Show for All Products')],
            ['value' => 'in_stock_only', 'label' => __('Show Only for In-Stock Products')],
            ['value' => 'out_of_stock_only', 'label' => __('Show Only for Out-of-Stock Products')],
            ['value' => 'low_stock_only', 'label' => __('Show Only for Low Stock Products')],
            ['value' => 'exclude_out_of_stock', 'label' => __('Hide for Out-of-Stock Products')]
        ];
    }
} 