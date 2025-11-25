<?php

namespace Gundo\ProductInfoAgent\Model;

use Magento\Framework\Model\AbstractModel;
use Gundo\ProductInfoAgent\Model\ResourceModel\SuggestionView as SuggestionViewResourceModel;

class SuggestionViewModel extends AbstractModel
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(SuggestionViewResourceModel::class);
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
     * @param  int|null $customerId
     * @return $this
     */
    public function setCustomerId(?int $customerId): self
    {
        return $this->setData('customer_id', $customerId);
    }

    /**
     * @return int|null
     */
    public function getCustomerId(): ?int
    {
        return $this->getData('customer_id');
    }

    /**
     * @param  string $sessionId
     * @return $this
     */
    public function setSessionId(string $sessionId): self
    {
        return $this->setData('session_id', $sessionId);
    }

    /**
     * @return string|null
     */
    public function getSessionId(): ?string
    {
        return $this->getData('session_id');
    }

    /**
     * @param  array $suggestions
     * @return $this
     */
    public function setViewedSuggestions(array $suggestions): self
    {
        return $this->setData('viewed_suggestions', json_encode($suggestions));
    }

    /**
     * @return array
     */
    public function getViewedSuggestions(): array
    {
        $data = $this->getData('viewed_suggestions');
        return $data ? json_decode($data, true) : [];
    }

    /**
     * Add a suggestion ID to the viewed list
     *
     * @param  int $suggestionId
     * @return $this
     */
    public function addViewedSuggestion(int $suggestionId): self
    {
        $viewed = $this->getViewedSuggestions();
        if (!in_array($suggestionId, $viewed)) {
            $viewed[] = $suggestionId;
            $this->setViewedSuggestions($viewed);
        }
        return $this;
    }

    /**
     * Check if a suggestion has been viewed
     *
     * @param  int $suggestionId
     * @return bool
     */
    public function hasSuggestionBeenViewed(int $suggestionId): bool
    {
        return in_array($suggestionId, $this->getViewedSuggestions());
    }
} 