<?php

namespace Gundo\ProductInfoAgent\Block;

use Exception;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableType;
use Magento\Framework\View\Element\Template;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\Registry;
use Magento\Customer\Helper\Session\CurrentCustomer as CustomerSessionHelper;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManager;
use Gundo\ProductInfoAgent\Helper\Data as ConfigHelper;
use Gundo\ProductInfoAgent\Model\AdvancedRulesEngine;
use Magento\Catalog\Api\ProductRepositoryInterface;

class Chat extends Template
{
    /**
     * @var Registry
     */
    protected Registry $registry;

    /**
     * @var ProductRepository
     */
    protected ProductRepository $productRepository;

    /**
     * @var CustomerSessionHelper
     */
    protected CustomerSessionHelper $customerSessionHelper;

    /**
     * @var StoreManager
     */
    protected StoreManager $storeManager;

    /**
     * @var ConfigHelper
     */
    protected ConfigHelper $configHelper;

    /**
     * @var AdvancedRulesEngine
     */
    protected AdvancedRulesEngine $advancedRulesEngine;

    /**
     * @param Context               $context
     * @param Registry              $registry
     * @param ProductRepository     $productRepository
     * @param CustomerSessionHelper $customerSessionHelper
     * @param StoreManager          $storeManager
     * @param ConfigHelper          $configHelper
     * @param AdvancedRulesEngine   $advancedRulesEngine
     * @param array                 $data
     */
    public function __construct(
        Template\Context      $context,
        Registry              $registry,
        ProductRepository     $productRepository,
        CustomerSessionHelper $customerSessionHelper,
        StoreManager          $storeManager,
        ConfigHelper          $configHelper,
        AdvancedRulesEngine   $advancedRulesEngine,
        array                 $data = []
    ) {
        parent::__construct($context, $data);
        $this->registry = $registry;
        $this->productRepository = $productRepository;
        $this->customerSessionHelper = $customerSessionHelper;
        $this->storeManager = $storeManager;
        $this->configHelper = $configHelper;
        $this->advancedRulesEngine = $advancedRulesEngine;
    }

    /**
     * @return Product|null
     */
    public function getCurrentProduct(): ?Product
    {
        return $this->registry->registry('current_product');
    }

    /**
     * @return int
     */
    public function getCustomer(): int
    {
        try {

            return $this->customerSessionHelper->getCustomer()->getId();
        } catch (Exception $e) {
            unset($e);
            return 0;
        }
    }

    /**
     * @return string
     */
    public function agentEndpoint(): string
    {
        return $this->getBaseUrl() . 'rest/V1/productinfoagent/message';
    }

    /**
     * @return string
     */
    public function getVoiceEndpoint(): string
    {
        return $this->getBaseUrl() . 'rest/V1/productinfoagent/voice';
    }

    /**
     * @return bool
     */
    public function isVoiceEnabled(): bool
    {
        return $this->configHelper->isVoiceEnabled();
    }

    /**
     * @return array
     */
    public function getThemeColors(): array
    {
        return $this->configHelper->getThemeColors();
    }

    /**
     * @return bool
     */
    public function isThemeAdaptationEnabled(): bool
    {
        return $this->configHelper->isThemeAdaptationEnabled();
    }

    /**
     * @return string
     */
    public function getVoiceLoadingMessage(): string
    {
        return $this->configHelper->getVoiceLoadingMessage();
    }

    /**
     * @return string
     */
    public function getVoiceReadyMessage(): string
    {
        return $this->configHelper->getVoiceReadyMessage();
    }

    /**
     * Check if auto-play voice responses is enabled
     *
     * @return bool
     */
    public function isAutoPlayVoiceEnabled(): bool
    {
        return $this->configHelper->isAutoPlayVoiceEnabled();
    }

    /**
     * Check if user is logged in
     *
     * @return bool
     */
    public function isUserLoggedIn(): bool
    {
        return $this->getCustomer() > 0;
    }

    /**
     * Check if auto voice for logged users is enabled
     *
     * @return bool
     */
    public function isAutoVoiceForLoggedUsersEnabled(): bool
    {
        return $this->configHelper->isAutoVoiceForLoggedUsersEnabled();
    }

    /**
     * Check if smart suggestion rotation is enabled
     *
     * @return bool
     */
    public function isSmartRotationEnabled(): bool
    {
        return $this->configHelper->isSmartRotationEnabled();
    }

    /**
     * Check if image processing is enabled
     *
     * @return bool
     */
    public function isImageProcessingEnabled(): bool
    {
        // Check if Imagine module is enabled and configured
        try {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $imagineHelper = $objectManager->get(\Gundo\Imagine\Helper\Data::class);
            return $imagineHelper->isImagineEnabled() && $imagineHelper->getApiKey();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get number of suggestions to show per load
     *
     * @return int
     */
    public function getSuggestionsPerLoad(): int
    {
        return $this->configHelper->getSuggestionsPerLoad();
    }

    /**
     * Check if chat should be displayed on current page type
     *
     * @return bool
     */
    public function shouldDisplayChat(): bool
    {
        if (!$this->configHelper->isProductInfoAgentEnabled()) {
            return false;
        }

        $currentProduct = $this->getCurrentProduct();
        $categoryId = null;
        
        // Get category ID for category pages
        if ($this->isCurrentPageCategory()) {
            $categoryId = (int)$this->getRequest()->getParam('id');
        }

        // Check basic page type rules first
        if ($currentProduct && $currentProduct->getId()) {
            if (!$this->configHelper->showOnProductPages()) {
                return false;
            }
        } elseif ($this->isCurrentPageCategory()) {
            if (!$this->configHelper->showOnCategoryPages()) {
                return false;
            }
        } elseif ($this->isCurrentPageCms()) {
            if (!$this->configHelper->showOnCmsPages()) {
                return false;
            }
        } else {
            return false; // Unknown page type
        }

        // Apply advanced rules if enabled
        return $this->advancedRulesEngine->shouldDisplayChat(
            $currentProduct, $categoryId, [
            'page_type' => $this->getCurrentPageType(),
            'page_identifier' => $this->getCurrentPageIdentifier(),
            'request' => $this->getRequest()
            ]
        );
    }

    /**
     * Check if current page is a category page
     *
     * @return bool
     */
    private function isCurrentPageCategory(): bool
    {
        $request = $this->getRequest();
        return ($request->getModuleName() === 'catalog' && $request->getActionName() === 'view') ||
               ($request->getModuleName() === 'catalogsearch' && $request->getActionName() === 'index');
    }

    /**
     * Check if current page is a CMS page
     *
     * @return bool
     */
    private function isCurrentPageCms(): bool
    {
        $request = $this->getRequest();
        return $request->getModuleName() === 'cms';
    }

    /**
     * Check if conversation persistence is enabled
     *
     * @return bool
     */
    public function isConversationPersistenceEnabled(): bool
    {
        return $this->configHelper->isConversationPersistenceEnabled();
    }

    /**
     * Get conversation persistence duration in hours
     *
     * @return int
     */
    public function getPersistenceDuration(): int
    {
        return $this->configHelper->getPersistenceDuration();
    }

    /**
     * Get current page type for context
     *
     * @return string
     */
    public function getCurrentPageType(): string
    {
        $currentProduct = $this->getCurrentProduct();
        
        if ($currentProduct && $currentProduct->getId()) {
            return 'product';
        }

        if ($this->isCurrentPageCategory()) {
            return 'category';
        }

        if ($this->isCurrentPageCms()) {
            return 'cms';
        }

        return 'unknown';
    }

    /**
     * Get current page identifier for persistence
     *
     * @return string
     */
    public function getCurrentPageIdentifier(): string
    {
        $currentProduct = $this->getCurrentProduct();
        
        if ($currentProduct && $currentProduct->getId()) {
            return 'product_' . $currentProduct->getId();
        }

        $request = $this->getRequest();
        $moduleName = $request->getModuleName();
        $actionName = $request->getActionName();

        if ($moduleName === 'catalog' && $actionName === 'view') {
            $categoryId = $request->getParam('id');
            return 'category_' . ($categoryId ?: 'unknown');
        }

        if ($moduleName === 'catalogsearch' && $actionName === 'index') {
            $query = $request->getParam('q', '');
            return 'search_' . md5($query);
        }

        if ($moduleName === 'cms') {
            $pageId = $request->getParam('page_id') ?: $request->getParam('id');
            if ($actionName === 'index') {
                return 'cms_home';
            }
            return 'cms_' . ($pageId ?: 'unknown');
        }

        return 'page_' . $moduleName . '_' . $actionName;
    }

    /**
     * Get chat display mode
     *
     * @return string
     */
    public function getChatDisplayMode(): string
    {
        return $this->configHelper->getChatDisplayMode() ?? 'embedded';
    }
}
