<?php declare(strict_types=1);

namespace Gundo\ProductInfoAgent\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Gundo\ProductInfoAgent\Logger\Logger;
use Gundo\ProductInfoAgent\Model\ProductInfoAgent;
use Gundo\ProductInfoAgent\Model\Queue\Publish\Publish;

class ProductInfoChatObserver implements ObserverInterface
{
    /**
     * @var Logger
     */
    protected Logger $logger;

    /**
     * @var ProductInfoAgent
     */
    protected ProductInfoAgent $productInfoAgent;

    /**
     * @var Publish
     */
    protected Publish $publish;

    /**
     * @param Logger           $logger
     * @param ProductInfoAgent $productInfoAgent
     * @param Publish          $publish
     */
    public function __construct(
        Logger $logger,
        ProductInfoAgent $productInfoAgent,
        Publish $publish
    ) {
        $this->logger = $logger;
        $this->productInfoAgent = $productInfoAgent;
        $this->publish = $publish;
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer): void
    {
        try {
            $chatData = $observer->getEvent()->getData('productInfoResponse');
            // Disabled RabbitMQ publishing to avoid connection errors
            // $this->publish->publish($chatData);
            $this->processConverseData($chatData);

        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            $this->logger->debug($e->getTraceAsString());
        }
    }

    /**
     * @param  $response
     * @return string
     */
    public function fullResponse($response): string
    {
        $fullResponse = '';

        foreach ($response as $message) {
            $fullResponse .= (string)$message;
        }

        return $fullResponse;
    }

    /**
     * @param  mixed $chatData
     * @return void
     * @throws \Exception
     */
    public function processConverseData(array $chatData): void
    {
        if ($chatData) {
            $response = $this->fullResponse($chatData['response']);

            $storeData = $this->productInfoAgent;
            $storeData->setMessage($chatData['message']);
            $storeData->setCreatedAt(Date('Y-m-d H:i:s'));
            $storeData->setData('product_id', $chatData['productid']);
            $storeData->setData('data_collection', ($response));
            $storeData->setModel($chatData['response']['model']);
            $storeData->setUserId($chatData['customerId']);
            $storeData->save();

            $this->logger->debug($chatData);
        }
    }
}
