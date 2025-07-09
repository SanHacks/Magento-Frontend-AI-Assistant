<?php declare(strict_types=1);

namespace Gundo\ProductInfoAgent\Block\Adminhtml;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Gundo\ProductInfoAgent\Model\ResourceModel\ProductInfoAgent\Collection;
use Gundo\ProductInfoAgent\Model\ResourceModel\ProductInfoAgent\CollectionFactory;

class History extends Template
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @param Context $context
     * @param CollectionFactory $collectionFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        CollectionFactory $collectionFactory,
        array $data = []
    ) {
        $this->collectionFactory = $collectionFactory;
        parent::__construct($context, $data);
    }

    /**
     * Get chat history collection
     *
     * @return Collection
     */
    public function getChatHistory(): Collection
    {
        $collection = $this->collectionFactory->create();
        $collection->setOrder('created_at', 'DESC');
        $collection->setPageSize(50); // Limit to 50 records per page
        
        return $collection;
    }

    /**
     * Format response time for display
     *
     * @param int $timeMs
     * @return string
     */
    public function formatResponseTime(int $timeMs): string
    {
        if ($timeMs < 1000) {
            return $timeMs . 'ms';
        } else {
            return round($timeMs / 1000, 1) . 's';
        }
    }

    /**
     * Truncate message for display
     *
     * @param string $message
     * @param int $length
     * @return string
     */
    public function truncateMessage(string $message, int $length = 100): string
    {
        if (strlen($message) <= $length) {
            return $message;
        }
        
        return substr($message, 0, $length) . '...';
    }

    /**
     * Get feedback badge class
     *
     * @param string $feedback
     * @return string
     */
    public function getFeedbackClass(string $feedback): string
    {
        switch ($feedback) {
            case 'positive':
                return 'badge-success';
            case 'negative':
                return 'badge-danger';
            default:
                return 'badge-secondary';
        }
    }
} 