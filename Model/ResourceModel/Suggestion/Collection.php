<?php

namespace Gundo\ProductInfoAgent\Model\ResourceModel\Suggestion;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Gundo\ProductInfoAgent\Model\SuggestionModel;
use Gundo\ProductInfoAgent\Model\ResourceModel\Suggestion as SuggestionResourceModel;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'productinfoagent_suggestion_collection';

    /**
     * Initialize collection model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(SuggestionModel::class, SuggestionResourceModel::class);
    }
} 