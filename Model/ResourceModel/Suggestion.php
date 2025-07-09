<?php

namespace Gundo\ProductInfoAgent\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Suggestion extends AbstractDb
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'productinfoagent_suggestion_resource_model';

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('productinfoagent_suggestion', 'suggestion_id');
    }
} 