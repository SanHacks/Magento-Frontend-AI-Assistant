<?php declare(strict_types=1);

namespace Gundo\ProductInfoAgent\Block\Adminhtml;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Gundo\ProductInfoAgent\Model\ResourceModel\ProductInfoAgent\Collection;
use Gundo\ProductInfoAgent\Model\ResourceModel\ProductInfoAgent\CollectionFactory;

class Dashboard extends Template
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @param Context           $context
     * @param CollectionFactory $collectionFactory
     * @param array             $data
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
     * Get total interactions count
     *
     * @return int
     */
    public function getTotalInteractions(): int
    {
        $collection = $this->collectionFactory->create();
        return $collection->getSize();
    }

    /**
     * Get voice usage percentage
     *
     * @return float
     */
    public function getVoiceUsagePercentage(): float
    {
        $collection = $this->collectionFactory->create();
        $total = $collection->getSize();
        
        if ($total === 0) {
            return 0.0;
        }
        
        $voiceCollection = $this->collectionFactory->create();
        $voiceCollection->addFieldToFilter('voice_requested', 1);
        $voiceCount = $voiceCollection->getSize();
        
        return round(($voiceCount / $total) * 100, 1);
    }

    /**
     * Get image processing usage count
     *
     * @return int
     */
    public function getImageUsageCount(): int
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('message', ['like' => '%[Image%']);
        return $collection->getSize();
    }

    /**
     * Get satisfaction rate
     *
     * @return float
     */
    public function getSatisfactionRate(): float
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('feedback', ['in' => ['positive', 'negative']]);
        $total = $collection->getSize();
        
        if ($total === 0) {
            return 0.0;
        }
        
        $positiveCollection = $this->collectionFactory->create();
        $positiveCollection->addFieldToFilter('feedback', 'positive');
        $positiveCount = $positiveCollection->getSize();
        
        return round(($positiveCount / $total) * 100, 1);
    }

    /**
     * Get average response time
     *
     * @return float
     */
    public function getAverageResponseTime(): float
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('response_time_ms', ['gt' => 0]);
        
        if ($collection->getSize() === 0) {
            return 0.0;
        }
        
        $connection = $collection->getConnection();
        $select = $connection->select()
            ->from($collection->getMainTable(), ['AVG(response_time_ms) as avg_time'])
            ->where('response_time_ms > 0');
            
        $result = $connection->fetchOne($select);
        return round((float)$result, 0);
    }

    /**
     * Get daily interactions data for chart
     *
     * @return array
     */
    public function getDailyInteractionsData(): array
    {
        $collection = $this->collectionFactory->create();
        $connection = $collection->getConnection();
        
        $select = $connection->select()
            ->from(
                $collection->getMainTable(), [
                'date' => 'DATE(created_at)',
                'count' => 'COUNT(*)'
                ]
            )
            ->where('created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)')
            ->group('DATE(created_at)')
            ->order('date ASC');
            
        $results = $connection->fetchAll($select);
        
        // Fill in missing days with 0
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $found = false;
            foreach ($results as $result) {
                if ($result['date'] === $date) {
                    $data[] = (int)$result['count'];
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $data[] = 0;
            }
        }
        
        return $data;
    }

    /**
     * Get feature usage data for chart
     *
     * @return array
     */
    public function getFeatureUsageData(): array
    {
        $collection = $this->collectionFactory->create();
        $total = $collection->getSize();
        
        if ($total === 0) {
            return [0, 0, 0, 0];
        }
        
        // Text chat (default)
        $textCount = $total;
        
        // Voice usage
        $voiceCollection = $this->collectionFactory->create();
        $voiceCollection->addFieldToFilter('voice_requested', 1);
        $voiceCount = $voiceCollection->getSize();
        $textCount -= $voiceCount;
        
        // Image processing
        $imageCollection = $this->collectionFactory->create();
        $imageCollection->addFieldToFilter('message', ['like' => '%[Image%']);
        $imageCount = $imageCollection->getSize();
        $textCount -= $imageCount;
        
        // Suggestions clicked
        $suggestionCollection = $this->collectionFactory->create();
        $suggestionCollection->addFieldToFilter('message', ['like' => '%[Suggestion%']);
        $suggestionCount = $suggestionCollection->getSize();
        $textCount -= $suggestionCount;
        
        // Ensure text count is not negative
        $textCount = max(0, $textCount);
        
        return [$textCount, $voiceCount, $imageCount, $suggestionCount];
    }

    /**
     * Get response time distribution data
     *
     * @return array
     */
    public function getResponseTimeData(): array
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('response_time_ms', ['gt' => 0]);
        $connection = $collection->getConnection();
        
        $ranges = [
            '< 1s' => [0, 1000],
            '1-2s' => [1000, 2000],
            '2-3s' => [2000, 3000],
            '3-5s' => [3000, 5000],
            '> 5s' => [5000, 999999]
        ];
        
        $data = [];
        foreach ($ranges as $label => $range) {
            $select = $connection->select()
                ->from($collection->getMainTable(), ['COUNT(*) as count'])
                ->where('response_time_ms >= ?', $range[0])
                ->where('response_time_ms < ?', $range[1]);
                
            $count = $connection->fetchOne($select);
            $data[] = (int)$count;
        }
        
        return $data;
    }

    /**
     * Get user engagement metrics
     *
     * @return array
     */
    public function getUserEngagementData(): array
    {
        $collection = $this->collectionFactory->create();
        $connection = $collection->getConnection();
        
        // Mock data for demonstration - replace with real metrics
        return [
            rand(70, 90), // New Users
            rand(60, 80), // Returning Users  
            rand(80, 95), // Active Sessions
            rand(70, 85), // Avg Session Time
            rand(75, 90)  // Feature Adoption
        ];
    }

    /**
     * Get recent activity data
     *
     * @return array
     */
    public function getRecentActivity(): array
    {
        $collection = $this->collectionFactory->create();
        $collection->setOrder('created_at', 'DESC');
        $collection->setPageSize(10);
        
        $activities = [];
        foreach ($collection as $item) {
            $type = 'Text Chat';
            if ($item->getVoiceRequested()) {
                $type = 'Voice Message';
            } elseif (strpos($item->getMessage(), '[Image') !== false) {
                $type = 'Image Processing';
            } elseif (strpos($item->getMessage(), '[Suggestion') !== false) {
                $type = 'Suggestion Click';
            }
            
            $activities[] = [
                'type' => $type,
                'description' => substr($item->getMessage(), 0, 50) . (strlen($item->getMessage()) > 50 ? '...' : ''),
                'time' => $item->getCreatedAt() ? date('M j, H:i', strtotime($item->getCreatedAt())) : 'Unknown'
            ];
        }
        
        return $activities;
    }

    /**
     * Get data URL for AJAX requests
     *
     * @return string
     */
    public function getDataUrl(): string
    {
        return $this->getUrl('productinfoagent/productinfoagent/data');
    }
} 