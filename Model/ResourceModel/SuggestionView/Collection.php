<?php

namespace Gundo\ProductInfoAgent\Model\ResourceModel\SuggestionView;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Gundo\ProductInfoAgent\Model\SuggestionViewModel;
use Gundo\ProductInfoAgent\Model\ResourceModel\SuggestionView as SuggestionViewResourceModel;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'productinfoagent_suggestion_view_collection';

    /**
     * Initialize collection model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(SuggestionViewModel::class, SuggestionViewResourceModel::class);
    }
} 