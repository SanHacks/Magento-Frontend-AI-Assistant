<?php

namespace Gundo\ProductInfoAgent\Block\Adminhtml;

use Magento\Framework\View\Element\Template;
use Gundo\ProductInfoAgent\Model\ResourceModel\ProductInfoAgent\Collection as ProductInfoAgent;

class Index extends Template
{

    /**
     * @var ProductInfoAgent
     */
    protected ProductInfoAgent $productInfoAgent;

    public function __construct(
        ProductInfoAgent $productInfoAgent,
        Template\Context $context,
    ) {
        $this->productInfoAgent = $productInfoAgent;
        parent::__construct($context);
    }

    /**
     * @return ProductInfoAgent
     */
    public function getProductInfoAgent(): ProductInfoAgent
    {
        return $this->productInfoAgent->load();
    }
}
