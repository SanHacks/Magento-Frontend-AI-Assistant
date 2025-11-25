<?php

namespace Gundo\ProductInfoAgent\Model\Queue\Publish;


use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Gundo\ProductInfoAgent\Logger\Logger;

class Publish
{
    /**
     * @var PublisherInterface
     */
    protected PublisherInterface $publisher;

    /**
     * @var Json
     */
    protected Json $serializer;

    /**
     * @var Logger
     */
    protected Logger $logger;

    /**
     * @param PublisherInterface $publisher
     * @param Json               $serializer
     * @param Logger             $logger
     */
    public function __construct(
        PublisherInterface $publisher,
        Json $serializer,
        Logger $logger
    ) {
        $this->publisher = $publisher;
        $this->serializer = $serializer;
        $this->logger = $logger;
    }

    /**
     * @param  $data
     * @return void
     */
    public function publish($data): void
    {
        $data = $this->serializer->serialize($data);

        try {
            $this->logger->info("Publishing product info data.");
            $this->publisher->publish('productinfoagent', $data);
            $this->logger->info("Published productinfoagent message successfully.");
        }catch (\Exception $exception){
            $this->logger->error($exception->getMessage());
        }
    }
}
