<?php

namespace Gundo\ProductInfoAgent\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class ProductInfoAgent extends AbstractDb
{
    /**
     * @var string
     */
    protected string $_eventPrefix = 'productinfoagento_ai_resource_model';

    /**
     * Initialize resource model.
     */
    protected function _construct(): void
    {
        $this->_init('productinfoagento_ai', 'chat_id');
        $this->_useIsObjectNew = true;
    }
}
