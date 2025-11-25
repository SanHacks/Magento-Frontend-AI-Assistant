<?php

namespace Gundo\ProductInfoAgent\Model;

use Gundo\ProductInfoAgent\Api\SuggestionInterface;
use Gundo\ProductInfoAgent\Model\ResourceModel\Suggestion\CollectionFactory as SuggestionCollectionFactory;
use Gundo\ProductInfoAgent\Model\ResourceModel\SuggestionView\CollectionFactory as SuggestionViewCollectionFactory;
use Gundo\ProductInfoAgent\Model\SuggestionModelFactory;
use Gundo\ProductInfoAgent\Model\SuggestionViewModelFactory;
use Gundo\ProductInfoAgent\Model\ResourceModel\Suggestion as SuggestionResourceModel;
use Gundo\ProductInfoAgent\Model\ResourceModel\SuggestionView as SuggestionViewResourceModel;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Gundo\ProductInfoAgent\Helper\Data as HelperData;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Session\SessionManagerInterface;

class Suggestion implements SuggestionInterface
{
    private SuggestionCollectionFactory $collectionFactory;
    private SuggestionViewCollectionFactory $viewCollectionFactory;
    private SuggestionModelFactory $suggestionFactory;
    private SuggestionViewModelFactory $suggestionViewFactory;
    private SuggestionResourceModel $suggestionResourceModel;
    private SuggestionViewResourceModel $suggestionViewResourceModel;
    private ProductRepositoryInterface $productRepository;
    private HelperData $helperData;
    private DateTime $dateTime;
    private CustomerSession $customerSession;
    private SessionManagerInterface $sessionManager;

    public function __construct(
        SuggestionCollectionFactory $collectionFactory,
        SuggestionViewCollectionFactory $viewCollectionFactory,
        SuggestionModelFactory $suggestionFactory,
        SuggestionViewModelFactory $suggestionViewFactory,
        SuggestionResourceModel $suggestionResourceModel,
        SuggestionViewResourceModel $suggestionViewResourceModel,
        ProductRepositoryInterface $productRepository,
        HelperData $helperData,
        DateTime $dateTime,
        CustomerSession $customerSession,
        SessionManagerInterface $sessionManager
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->viewCollectionFactory = $viewCollectionFactory;
        $this->suggestionFactory = $suggestionFactory;
        $this->suggestionViewFactory = $suggestionViewFactory;
        $this->suggestionResourceModel = $suggestionResourceModel;
        $this->suggestionViewResourceModel = $suggestionViewResourceModel;
        $this->productRepository = $productRepository;
        $this->helperData = $helperData;
        $this->dateTime = $dateTime;
        $this->customerSession = $customerSession;
        $this->sessionManager = $sessionManager;
    }

    /**
     * @inheritDoc
     */
    public function getSuggestions(int $productId): array
    {
        // Get or create suggestions for this product
        $suggestions = $this->getOrCreateSuggestions($productId);
        
        if (empty($suggestions)) {
            return [];
        }

        // Apply smart rotation if enabled
        if ($this->helperData->isSmartRotationEnabled()) {
            $suggestions = $this->applySmartRotation($productId, $suggestions);
        }

        // Apply randomization if enabled
        if ($this->helperData->isRandomizeSuggestionsEnabled()) {
            $suggestions = $this->randomizeSuggestions($suggestions);
        }

        // Limit to configured number per load
        $suggestionsPerLoad = $this->helperData->getSuggestionsPerLoad();
        $suggestions = array_slice($suggestions, 0, $suggestionsPerLoad);

        // Track viewed suggestions
        $this->trackViewedSuggestions($productId, $suggestions);

        return array_column($suggestions, 'question');
    }

    /**
     * Get or create suggestions for a product
     *
     * @param  int $productId
     * @return array
     */
    private function getOrCreateSuggestions(int $productId): array
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('product_id', $productId)
            ->addFieldToFilter('is_active', 1)
            ->setOrder('priority', 'ASC')
            ->setOrder('created_at', 'DESC');

        $cacheLifetime = $this->helperData->getSuggestionsCacheLifetime();

        // Check if we have cached suggestions that are still valid
        if ($cacheLifetime > 0 && $collection->getSize() > 0) {
            $firstItem = $collection->getFirstItem();
            $createdAt = new \DateTime($firstItem->getData('created_at'));
            $now = new \DateTime();
            $diff = $now->diff($createdAt)->days;

            if ($diff <= $cacheLifetime) {
                // Return cached suggestions as array with IDs
                $suggestions = [];
                foreach ($collection as $suggestion) {
                    $suggestions[] = [
                        'id' => $suggestion->getId(),
                        'question' => $suggestion->getQuestion(),
                        'priority' => $suggestion->getData('priority')
                    ];
                }
                return $suggestions;
            } else {
                // Invalidate expired cache
                foreach ($collection as $item) {
                    $this->suggestionResourceModel->delete($item);
                }
            }
        }

        // Generate new suggestions
        return $this->generateNewSuggestions($productId);
    }

    /**
     * Generate new suggestions for a product
     *
     * @param  int $productId
     * @return array
     */
    private function generateNewSuggestions(int $productId): array
    {
        try {
            $product = $this->productRepository->getById($productId);
        } catch (\Exception $e) {
            return [];
        }

        $baseQuestions = [
            ['question' => 'Tell me more about ' . $product->getName(), 'priority' => 10],
            ['question' => 'What are the key features of this product?', 'priority' => 20],
            ['question' => 'How do I use this product?', 'priority' => 30],
            ['question' => 'What other products would you recommend with this?', 'priority' => 40],
        ];

        // Add conditional questions based on product data
        if ($product->getShortDescription() || $product->getDescription()) {
            $baseQuestions[] = ['question' => 'Can you summarize the description for me?', 'priority' => 15];
        }
        
        if ($product->getWeight()) {
            $baseQuestions[] = ['question' => 'How much does this product weigh?', 'priority' => 25];
        }

        if ($product->getPrice()) {
            $baseQuestions[] = ['question' => 'Is this product good value for money?', 'priority' => 35];
        }

        // Add variety questions for better rotation
        $varietyQuestions = [
            ['question' => 'What makes this product special?', 'priority' => 50],
            ['question' => 'Who is this product best suited for?', 'priority' => 60],
            ['question' => 'How does this compare to similar products?', 'priority' => 70],
            ['question' => 'What are customers saying about this product?', 'priority' => 80],
            ['question' => 'Are there any special care instructions?', 'priority' => 90],
            ['question' => 'What warranty or guarantee comes with this?', 'priority' => 100],
        ];

        // Merge and randomize variety questions to add freshness
        shuffle($varietyQuestions);
        $selectedVariety = array_slice($varietyQuestions, 0, 3); // Take 3 random variety questions
        $allQuestions = array_merge($baseQuestions, $selectedVariety);

        // Save suggestions to database
        $savedSuggestions = [];
        foreach ($allQuestions as $questionData) {
            $suggestionModel = $this->suggestionFactory->create();
            $suggestionModel->setData(
                [
                'product_id' => $productId,
                'question' => $questionData['question'],
                'priority' => $questionData['priority'],
                'is_active' => 1
                ]
            );
            
            try {
                $this->suggestionResourceModel->save($suggestionModel);
                $savedSuggestions[] = [
                    'id' => $suggestionModel->getId(),
                    'question' => $questionData['question'],
                    'priority' => $questionData['priority']
                ];
            } catch (\Exception $e) {
                // Continue if one suggestion fails to save
                continue;
            }
        }

        return $savedSuggestions;
    }

    /**
     * Apply smart rotation based on user's viewed suggestions
     *
     * @param  int   $productId
     * @param  array $suggestions
     * @return array
     */
    private function applySmartRotation(int $productId, array $suggestions): array
    {
        $customerId = $this->customerSession->getCustomerId();
        $sessionId = $this->sessionManager->getSessionId();

        // Get user's viewed suggestions
        $viewCollection = $this->viewCollectionFactory->create();
        $viewCollection->addFieldToFilter('product_id', $productId);
        
        if ($customerId) {
            $viewCollection->addFieldToFilter('customer_id', $customerId);
        } else {
            $viewCollection->addFieldToFilter('session_id', $sessionId)
                ->addFieldToFilter('customer_id', ['null' => true]);
        }

        $viewedSuggestionIds = [];
        if ($viewCollection->getSize() > 0) {
            $viewRecord = $viewCollection->getFirstItem();
            $viewedSuggestionIds = $viewRecord->getViewedSuggestions();
        }

        // Separate viewed and unviewed suggestions
        $unviewedSuggestions = [];
        $viewedSuggestions = [];

        foreach ($suggestions as $suggestion) {
            if (in_array($suggestion['id'], $viewedSuggestionIds)) {
                $viewedSuggestions[] = $suggestion;
            } else {
                $unviewedSuggestions[] = $suggestion;
            }
        }

        // Prioritize unviewed suggestions, then add viewed ones
        $rotatedSuggestions = array_merge($unviewedSuggestions, $viewedSuggestions);

        // If all suggestions have been viewed, reset the tracking to start fresh rotation
        if (empty($unviewedSuggestions) && !empty($viewedSuggestions)) {
            $this->resetViewedSuggestions($productId, $customerId, $sessionId);
            // Re-randomize for fresh start
            shuffle($rotatedSuggestions);
        }

        return $rotatedSuggestions;
    }

    /**
     * Randomize suggestions array
     *
     * @param  array $suggestions
     * @return array
     */
    private function randomizeSuggestions(array $suggestions): array
    {
        // Create a copy to avoid modifying original
        $randomized = $suggestions;
        
        // Use a seeded shuffle for consistent randomization within the same session
        // but different across different sessions/users
        $seed = crc32($this->sessionManager->getSessionId() . date('Y-m-d-H'));
        mt_srand($seed);
        shuffle($randomized);
        
        return $randomized;
    }

    /**
     * Track viewed suggestions for the user/session
     *
     * @param  int   $productId
     * @param  array $suggestions
     * @return void
     */
    private function trackViewedSuggestions(int $productId, array $suggestions): void
    {
        if (!$this->helperData->isSmartRotationEnabled()) {
            return;
        }

        $customerId = $this->customerSession->getCustomerId();
        $sessionId = $this->sessionManager->getSessionId();
        $suggestionIds = array_column($suggestions, 'id');

        // Find existing view record
        $viewCollection = $this->viewCollectionFactory->create();
        $viewCollection->addFieldToFilter('product_id', $productId);
        
        if ($customerId) {
            $viewCollection->addFieldToFilter('customer_id', $customerId);
        } else {
            $viewCollection->addFieldToFilter('session_id', $sessionId)
                ->addFieldToFilter('customer_id', ['null' => true]);
        }

        $viewRecord = $viewCollection->getFirstItem();
        
        if ($viewRecord->getId()) {
            // Update existing record
            $currentViewed = $viewRecord->getViewedSuggestions();
            $newViewed = array_unique(array_merge($currentViewed, $suggestionIds));
            $viewRecord->setViewedSuggestions($newViewed);
        } else {
            // Create new record
            $viewRecord = $this->suggestionViewFactory->create();
            $viewRecord->setProductId($productId);
            $viewRecord->setCustomerId($customerId);
            $viewRecord->setSessionId($sessionId);
            $viewRecord->setViewedSuggestions($suggestionIds);
        }

        try {
            $this->suggestionViewResourceModel->save($viewRecord);
        } catch (\Exception $e) {
            // Fail silently to not break suggestion functionality
        }
    }

    /**
     * Reset viewed suggestions for fresh rotation
     *
     * @param  int      $productId
     * @param  int|null $customerId
     * @param  string   $sessionId
     * @return void
     */
    private function resetViewedSuggestions(int $productId, ?int $customerId, string $sessionId): void
    {
        $viewCollection = $this->viewCollectionFactory->create();
        $viewCollection->addFieldToFilter('product_id', $productId);
        
        if ($customerId) {
            $viewCollection->addFieldToFilter('customer_id', $customerId);
        } else {
            $viewCollection->addFieldToFilter('session_id', $sessionId)
                ->addFieldToFilter('customer_id', ['null' => true]);
        }

        foreach ($viewCollection as $viewRecord) {
            try {
                $this->suggestionViewResourceModel->delete($viewRecord);
            } catch (\Exception $e) {
                // Continue if deletion fails
            }
        }
    }
} 