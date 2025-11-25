<?php

namespace Gundo\ProductInfoAgent\Model;

use Magento\Framework\Model\AbstractModel;
use Gundo\ProductInfoAgent\Model\ResourceModel\Suggestion as SuggestionResourceModel;

class SuggestionModel extends AbstractModel
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(SuggestionResourceModel::class);
    }

    /**
     * @param  int $productId
     * @return $this
     */
    public function setProductId(int $productId): self
    {
        return $this->setData('product_id', $productId);
    }

    /**
     * @return int|null
     */
    public function getProductId(): ?int
    {
        return $this->getData('product_id');
    }

    /**
     * @param  string $question
     * @return $this
     */
    public function setQuestion(string $question): self
    {
        return $this->setData('question', $question);
    }

    /**
     * @return string|null
     */
    public function getQuestion(): ?string
    {
        return $this->getData('question');
    }

    /**
     * @param  string $createdAt
     * @return $this
     */
    public function setCreatedAt(string $createdAt): self
    {
        return $this->setData('created_at', $createdAt);
    }

    /**
     * @return string|null
     */
    public function getCreatedAt(): ?string
    {
        return $this->getData('created_at');
    }
} 