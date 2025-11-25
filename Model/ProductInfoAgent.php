<?php

namespace Gundo\ProductInfoAgent\Model;

use Gundo\ProductInfoAgent\Api\Data\ProductInfoAgentInterface;
use Gundo\ProductInfoAgent\Model\ResourceModel\ProductInfoAgent as ResourceModel;
use Magento\Framework\Model\AbstractModel;

class ProductInfoAgent extends AbstractModel implements ProductInfoAgentInterface
{
    /**
     * Event prefix for the model.
     *
     * @var string
     */
    protected $_eventPrefix = 'productinfoagent_model';

    /**
     * Initialize the model.
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(ResourceModel::class);
    }

    /**
     * Get the message.
     *
     * @return string|null
     */
    public function getMessage(): ?string
    {
        return $this->getData(self::MESSAGE);
    }

    /**
     * Set the message.
     *
     * @param  string|null $message
     * @return $this
     */
    public function setMessage(?string $message): ProductInfoAgentInterface
    {
        return $this->setData(self::MESSAGE, $message);
    }

    /**
     * @param  string|null $data
     * @return ProductInfoAgentInterface
     */
    public function setDataCollection(?string $data): ProductInfoAgentInterface
    {
        return $this->setData(self::DATA, $data);
    }

    public function getDataCollection(): ?string
    {
        return $this->getData(self::DATA);
    }

    /**
     * Get the updated timestamp.
     *
     * @return string|null
     */
    public function getUpdatedAt(): ?string
    {
        return $this->getData(self::UPDATED_AT);
    }

    /**
     * Set the updated timestamp.
     *
     * @param  string|null $updatedAt
     * @return $this
     */
    public function setUpdatedAt(?string $updatedAt): ProductInfoAgentInterface
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }

    /**
     * Get the created timestamp.
     *
     * @return string|null
     */
    public function getCreatedAt(): ?string
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * Set the created timestamp.
     *
     * @param  string|null $createdAt
     * @return $this
     */
    public function setCreatedAt(?string $createdAt): ProductInfoAgentInterface
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * Get the user ID.
     *
     * @return int|null
     */
    public function getUserId(): ?int
    {
        return $this->getData(self::USER_ID);
    }

    /**
     * Set the user ID.
     *
     * @param  int|null $userId
     * @return $this
     */
    public function setUserId(?int $userId): ProductInfoAgentInterface
    {
        return $this->setData(self::USER_ID, $userId);
    }

    /**
     * Get the model.
     *
     * @return string|null
     */
    public function getModel(): ?string
    {
        return $this->getData(self::MODEL);
    }

    /**
     * Set the model.
     *
     * @param  string|null $model
     * @return $this
     */
    public function setModel(?string $model): ProductInfoAgentInterface
    {
        return $this->setData(self::MODEL, $model);
    }

    /**
     * Get the chat ID.
     *
     * @return int|null
     */
    public function getChatId(): ?int
    {
        return $this->getData(self::ID);
    }

    /**
     * Set the chat ID.
     *
     * @param  int|null $chatId
     * @return $this
     */
    public function setChatId(?int $chatId): ProductInfoAgent
    {
        return $this->setData(self::ID, $chatId);
    }


    /**
     * Save the model and manage timestamps.
     *
     * @param  array $data
     * @return $this
     * @throws \Exception
     */
    public function save(array $data = []): self
    {
        if (!empty($data)) {
            $this->setData($data);
            if (!$this->getId()) {
                $this->setCreatedAt($this->getCurrentTimestamp());
            }
            $this->setUpdatedAt($this->getCurrentTimestamp());
        }

        parent::save();

        return $this;
    }

    /**
     * Get current timestamp in the preferred format.
     *
     * @return string
     */
    private function getCurrentTimestamp(): string
    {
        return (new \DateTime())->format('Y-m-d H:i:s');
    }

    /**
     * @param  int|null $product
     * @return self
     */
    public function setProductId(?int $product): ProductInfoAgentInterface
    {
        return $this->setData(self::PRODUCT, $product);
    }

    /**
     * @return string|null
     */
    public function getProductId(): ?string
    {
        return $this->getData(self::PRODUCT);
    }
}
