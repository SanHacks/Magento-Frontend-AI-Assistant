<?php declare(strict_types=1);

namespace Gundo\ProductInfoAgent\Controller\Adminhtml\Dashboard;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Gundo\ProductInfoAgent\Block\Adminhtml\Dashboard;
use Psr\Log\LoggerInterface;

class Data extends Action
{
    /**
     * @var JsonFactory
     */
    private $jsonFactory;

    /**
     * @var Dashboard
     */
    private $dashboardBlock;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param Dashboard $dashboardBlock
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        Dashboard $dashboardBlock,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->dashboardBlock = $dashboardBlock;
        $this->logger = $logger;
    }

    /**
     * Execute dashboard data request
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $result = $this->jsonFactory->create();

        try {
            $data = [
                'success' => true,
                'data' => [
                    'metrics' => [
                        'total_interactions' => $this->dashboardBlock->getTotalInteractions(),
                        'voice_usage_percentage' => $this->dashboardBlock->getVoiceUsagePercentage(),
                        'image_usage_count' => $this->dashboardBlock->getImageUsageCount(),
                        'satisfaction_rate' => $this->dashboardBlock->getSatisfactionRate(),
                        'interactions_trend' => $this->calculateTrend('interactions'),
                        'voice_trend' => $this->calculateTrend('voice'),
                        'image_trend' => $this->calculateTrend('image'),
                        'satisfaction_trend' => $this->calculateTrend('satisfaction')
                    ],
                    'charts' => [
                        'daily_interactions' => $this->dashboardBlock->getDailyInteractionsData(),
                        'feature_usage' => $this->dashboardBlock->getFeatureUsageData(),
                        'response_times' => $this->dashboardBlock->getResponseTimeData(),
                        'user_engagement' => $this->dashboardBlock->getUserEngagementData()
                    ],
                    'recent_activity' => $this->dashboardBlock->getRecentActivity()
                ]
            ];

            return $result->setData($data);

        } catch (\Exception $e) {
            $this->logger->error('Dashboard data error: ' . $e->getMessage());
            
            return $result->setData([
                'success' => false,
                'error' => 'Failed to load dashboard data'
            ]);
        }
    }

    /**
     * Calculate trend percentage for metrics
     *
     * @param string $metric
     * @return string
     */
    private function calculateTrend(string $metric): string
    {
        // Mock trend calculation - replace with real logic
        $trends = [
            'interactions' => ['+12%', '+8%', '+15%', '+5%'],
            'voice' => ['+5%', '+3%', '+8%', '+2%'],
            'image' => ['+8%', '+12%', '+6%', '+10%'],
            'satisfaction' => ['+3%', '+1%', '+5%', '+2%']
        ];
        
        $trendOptions = $trends[$metric] ?? ['+0%'];
        return $trendOptions[array_rand($trendOptions)];
    }

    /**
     * Check if user has access to this action
     *
     * @return bool
     */
    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('Gundo_ProductInfoAgent::dashboard');
    }
} 