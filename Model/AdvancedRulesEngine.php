<?php
declare(strict_types=1);

namespace Gundo\ProductInfoAgent\Model;

use Gundo\ProductInfoAgent\Helper\Data as ConfigHelper;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Psr\Log\LoggerInterface;

/**
 * Advanced Rules Engine for ProductInfoAgent display logic
 */
class AdvancedRulesEngine
{
    private ConfigHelper $configHelper;
    private ProductRepositoryInterface $productRepository;
    private CategoryCollectionFactory $categoryCollectionFactory;
    private CustomerSession $customerSession;
    private DateTime $dateTime;
    private StockRegistryInterface $stockState;
    private LoggerInterface $logger;

    public function __construct(
        ConfigHelper $configHelper,
        ProductRepositoryInterface $productRepository,
        CategoryCollectionFactory $categoryCollectionFactory,
        CustomerSession $customerSession,
        DateTime $dateTime,
        StockRegistryInterface $stockState,
        LoggerInterface $logger
    ) {
        $this->configHelper = $configHelper;
        $this->productRepository = $productRepository;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->customerSession = $customerSession;
        $this->dateTime = $dateTime;
        $this->stockState = $stockState;
        $this->logger = $logger;
    }

    /**
     * Check if chat should be displayed based on advanced rules
     *
     * @param  Product|null $product
     * @param  int|null     $categoryId
     * @param  array        $context
     * @return bool
     */
    public function shouldDisplayChat(?Product $product = null, ?int $categoryId = null, array $context = []): bool
    {
        if (!$this->configHelper->isAdvancedRulesEnabled()) {
            return true; // If advanced rules are disabled, show everywhere
        }

        try {
            // Check time-based rules first (most restrictive)
            if (!$this->checkTimeBasedRules()) {
                return false;
            }

            // Check customer segment rules
            if (!$this->checkCustomerSegmentRules()) {
                return false;
            }

            // Check category rules
            if ($categoryId && !$this->checkCategoryRules($categoryId)) {
                return false;
            }

            // Check product-specific rules
            if ($product) {
                if (!$this->checkProductTypeRules($product)) {
                    return false;
                }

                if (!$this->checkStockRules($product)) {
                    return false;
                }

                if (!$this->checkAttributeFilters($product)) {
                    return false;
                }
            }

            return true;
        } catch (\Exception $e) {
            $this->logger->error('AdvancedRulesEngine error: ' . $e->getMessage());
            return true; // Fail open - show chat if there's an error
        }
    }

    /**
     * Check time-based rules
     *
     * @return bool
     */
    private function checkTimeBasedRules(): bool
    {
        $rules = $this->configHelper->getTimeBasedRules();
        if (empty($rules)) {
            return true;
        }

        $currentTime = $this->dateTime->gmtDate('H:i');
        $currentDay = strtolower($this->dateTime->gmtDate('l'));

        // Check business hours
        if (isset($rules['business_hours'])) {
            $start = $rules['business_hours']['start'] ?? '00:00';
            $end = $rules['business_hours']['end'] ?? '23:59';
            
            if ($currentTime < $start || $currentTime > $end) {
                return false;
            }
        }

        // Check allowed days
        if (isset($rules['days']) && is_array($rules['days'])) {
            $allowedDays = array_map('strtolower', $rules['days']);
            if (!in_array($currentDay, $allowedDays)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check customer segment rules
     *
     * @return bool
     */
    private function checkCustomerSegmentRules(): bool
    {
        $rules = $this->configHelper->getCustomerSegmentRules();
        if (empty($rules)) {
            return true;
        }

        $customerId = $this->customerSession->getCustomerId();
        if (!$customerId) {
            // Handle guest customer rules if defined
            return !isset($rules['registered_only']) || !$rules['registered_only'];
        }

        // Check VIP customer rules
        if (isset($rules['vip_customers'])) {
            $minOrders = $rules['vip_customers']['min_orders'] ?? 0;
            // This would require order history check - simplified for now
            if ($minOrders > 0) {
                // Implementation would check customer's order count
            }
        }

        // Check new customer rules
        if (isset($rules['new_customers'])) {
            $maxDays = $rules['new_customers']['max_days_registered'] ?? 30;
            // This would require customer registration date check
        }

        return true;
    }

    /**
     * Check category rules
     *
     * @param  int $categoryId
     * @return bool
     */
    private function checkCategoryRules(int $categoryId): bool
    {
        $rules = $this->configHelper->getCategoryRules();
        if (empty($rules)) {
            return true;
        }

        // Check excluded categories
        if (isset($rules['exclude_categories']) && is_array($rules['exclude_categories'])) {
            if (in_array($categoryId, $rules['exclude_categories'])) {
                return false;
            }
        }

        // Check included categories (if specified, only these are allowed)
        if (isset($rules['include_categories']) && is_array($rules['include_categories'])) {
            if (!in_array($categoryId, $rules['include_categories'])) {
                return false;
            }
        }

        // Check category conditions
        if (isset($rules['category_conditions'][$categoryId])) {
            $conditions = $rules['category_conditions'][$categoryId];
            
            // Check minimum products in category
            if (isset($conditions['min_products'])) {
                try {
                    $category = $this->categoryCollectionFactory->create()
                        ->addFieldToFilter('entity_id', $categoryId)
                        ->getFirstItem();
                    
                    if ($category->getProductCount() < $conditions['min_products']) {
                        return false;
                    }
                } catch (\Exception $e) {
                    $this->logger->warning('Category condition check failed: ' . $e->getMessage());
                }
            }
        }

        return true;
    }

    /**
     * Check product type rules
     *
     * @param  Product $product
     * @return bool
     */
    private function checkProductTypeRules(Product $product): bool
    {
        $allowedTypes = $this->configHelper->getProductTypeRules();
        if (empty($allowedTypes)) {
            return true; // No restrictions
        }

        return in_array($product->getTypeId(), $allowedTypes);
    }

    /**
     * Check stock rules
     *
     * @param  Product $product
     * @return bool
     */
    private function checkStockRules(Product $product): bool
    {
        $stockRule = $this->configHelper->getStockRules();
        if ($stockRule === 'all') {
            return true;
        }

        try {
            $stockItem = $this->stockState->getStockItem($product->getId());
            $isInStock = $stockItem->getIsInStock();
            $qty = $stockItem->getQty();

            switch ($stockRule) {
            case 'in_stock_only':
                return $isInStock;
            case 'out_of_stock_only':
                return !$isInStock;
            case 'low_stock_only':
                $minQty = $stockItem->getMinQty() ?: 5; // Default threshold
                return $isInStock && $qty <= $minQty;
            case 'exclude_out_of_stock':
                return $isInStock;
            default:
                return true;
            }
        } catch (\Exception $e) {
            $this->logger->warning('Stock rule check failed: ' . $e->getMessage());
            return true; // Fail open
        }
    }

    /**
     * Check attribute filters
     *
     * @param  Product $product
     * @return bool
     */
    private function checkAttributeFilters(Product $product): bool
    {
        $filters = $this->configHelper->getAttributeFilters();
        if (empty($filters)) {
            return true;
        }

        foreach ($filters as $attributeCode => $filterValue) {
            try {
                $productValue = $product->getData($attributeCode);
                
                if (is_array($filterValue)) {
                    // Handle array values (e.g., multiselect attributes)
                    if (isset($filterValue['min']) || isset($filterValue['max'])) {
                        // Handle range filters (e.g., price)
                        $numericValue = (float)$productValue;
                        if (isset($filterValue['min']) && $numericValue < $filterValue['min']) {
                            return false;
                        }
                        if (isset($filterValue['max']) && $numericValue > $filterValue['max']) {
                            return false;
                        }
                    } else {
                        // Handle array of allowed values
                        if (!in_array($productValue, $filterValue)) {
                            return false;
                        }
                    }
                } else {
                    // Handle single value comparison
                    if ($productValue != $filterValue) {
                        return false;
                    }
                }
            } catch (\Exception $e) {
                $this->logger->warning("Attribute filter check failed for {$attributeCode}: " . $e->getMessage());
                continue; // Skip this filter if there's an error
            }
        }

        return true;
    }
} 