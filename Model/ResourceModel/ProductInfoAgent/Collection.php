<?php

namespace Gundo\ProductInfoAgent\Model\ResourceModel\ProductInfoAgent;

use Gundo\ProductInfoAgent\Model\ProductInfoAgent as Model;
use Gundo\ProductInfoAgent\Model\ResourceModel\ProductInfoAgent as ResourceModel;
use Magento\Framework\DataObject;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * Event prefix for the collection.
     *
     * @var string
     */
    protected $_eventPrefix = 'productinfoagento_ai_collection';

    /**
     * Initialize the collection.
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(Model::class, ResourceModel::class);
    }

    /**
     * Retrieve an item by its ID.
     *
     * @param int $id
     * @return DataObject|null
     */
    public function getById(int $id): ?DataObject
    {
        return $this->getItemById($id) ?: null;
    }

    /**
     * @return Collection|null
     */
    public function getCollection(): ?Collection
    {
        return $this->load() ?: null;
    }
}
