<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Gundo\ProductInfoAgent\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;

/**
 * ProductInfoAgent configuration helper
 *
 * Provides access to module configuration settings including
 * image processing, voice functionality, and AI service settings.
 */
class Data extends AbstractHelper
{
    /**
     * XML paths for configuration
     */
    const XML_PATH_IMAGINE_ENABLED = 'productinfoagent/imagine/enabled';
    const XML_PATH_IMAGINE_API_KEY = 'productinfoagent/imagine/api_key';
    const XML_PATH_DALLE_API_KEY = 'productinfoagent/dalle/api_key';
    const XML_PATH_GEMINI_API_KEY = 'productinfoagent/gemini/api_key';
    const XML_PATH_IMAGE_AI_PROVIDER = 'productinfoagent/image/ai_provider';
    const XML_PATH_VOICE_ENABLED = 'productinfoagent/voice/enabled';
    const XML_PATH_VOICE_AUTOPLAY_ENABLED = 'productinfoagent/voice/autoplay_enabled';
    const XML_PATH_THEME_COLORS = 'productinfoagent/theme/colors';
    const XML_PATH_THEME_ADAPTATION_ENABLED = 'productinfoagent/theme/adaptation_enabled';
    const XML_PATH_VOICE_LOADING_MESSAGE = 'productinfoagent/voice/loading_message';
    const XML_PATH_VOICE_READY_MESSAGE = 'productinfoagent/voice/ready_message';
    const XML_PATH_VOICE_AUTOPLAY_LOGGED_USERS = 'productinfoagent/voice/autoplay_logged_users';
    const XML_PATH_SMART_ROTATION_ENABLED = 'productinfoagent/suggestions/smart_rotation';
    const XML_PATH_SUGGESTIONS_PER_LOAD = 'productinfoagent/suggestions/per_load';
    const XML_PATH_PRODUCTINFOAGENT_ENABLED = 'productinfoagent/general/enabled';
    const XML_PATH_SHOW_ON_PRODUCT_PAGES = 'productinfoagent/display/show_on_product_pages';
    const XML_PATH_SHOW_ON_CATEGORY_PAGES = 'productinfoagent/display/show_on_category_pages';
    const XML_PATH_SHOW_ON_CMS_PAGES = 'productinfoagent/display/show_on_cms_pages';
    const XML_PATH_CONVERSATION_PERSISTENCE_ENABLED = 'productinfoagent/conversation/persistence_enabled';
    const XML_PATH_PERSISTENCE_DURATION = 'productinfoagent/conversation/persistence_duration';
    const XML_PATH_CHAT_DISPLAY_MODE = 'productinfoagent/chat/display_mode';
    const XML_PATH_ADVANCED_RULES_ENABLED = 'productinfoagent/advanced_rules/enable_advanced_rules';
    const XML_PATH_SELECTED_MODEL = 'productinfoagent/general/selected_model';
    const XML_PATH_TIME_BASED_RULES = 'productinfoagent/advanced_rules/time_based_rules';
    const XML_PATH_CUSTOMER_SEGMENT_RULES = 'productinfoagent/advanced_rules/customer_segment_rules';
    const XML_PATH_CATEGORY_RULES = 'productinfoagent/advanced_rules/category_rules';
    const XML_PATH_PRODUCT_TYPE_RULES = 'productinfoagent/advanced_rules/product_type_rules';
    const XML_PATH_STOCK_RULES = 'productinfoagent/advanced_rules/stock_rules';
    const XML_PATH_ATTRIBUTE_FILTERS = 'productinfoagent/advanced_rules/attribute_filters';
    const XML_PATH_DEEPGRAM_API_KEY = 'productinfoagent/voice/deepgram_api_key';
    const XML_PATH_VOICE_MODEL = 'productinfoagent/voice/model';
    const XML_PATH_VOICE_CACHE_LIFETIME = 'productinfoagent/voice/cache_lifetime';
    const XML_PATH_GUEST_ALLOWED = 'productinfoagent/general/allow_guests';
    const XML_PATH_SYSTEM_PROMPT = 'productinfoagent/general/system_prompt';
    const XML_PATH_SUGGESTIONS_CACHE_LIFETIME = 'productinfoagent/suggestions/cache_lifetime';

    /**
     * Constructor
     *
     * @param Context $context Application context
     */
    public function __construct(
        Context $context
    ) {
        parent::__construct($context);
    }

    /**
     * Check if Imagine module is enabled
     *
     * @return bool
     */
    public function isImagineEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_IMAGINE_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get Imagine API key
     *
     * @return string
     */
    public function getApiKey(): string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_IMAGINE_API_KEY,
            ScopeInterface::SCOPE_STORE
        ) ?? '';
    }

    /**
     * Get DALL-E API key
     *
     * @return string
     */
    public function getDalleApiKey(): string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_DALLE_API_KEY,
            ScopeInterface::SCOPE_STORE
        ) ?? '';
    }

    /**
     * Get Gemini API key
     *
     * @return string
     */
    public function getGeminiApiKey(): string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_GEMINI_API_KEY,
            ScopeInterface::SCOPE_STORE
        ) ?? '';
    }

    /**
     * Get Image AI provider
     *
     * @return string
     */
    public function getImageAiProvider(): string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_IMAGE_AI_PROVIDER,
            ScopeInterface::SCOPE_STORE
        ) ?? 'dalle';
    }

    /**
     * Check if voice functionality is enabled
     *
     * @return bool
     */
    public function isVoiceEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_VOICE_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Check if voice auto-play is enabled
     *
     * @return bool
     */
    public function isAutoPlayVoiceEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_VOICE_AUTOPLAY_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get theme colors
     *
     * @return array
     */
    public function getThemeColors(): array
    {
        $colors = $this->scopeConfig->getValue(
            self::XML_PATH_THEME_COLORS,
            ScopeInterface::SCOPE_STORE
        );
        
        if (empty($colors)) {
            return [
                'primary' => '#007bff',
                'secondary' => '#6c757d',
                'accent' => '#28a745',
                'background' => '#ffffff',
                'text' => '#333333'
            ];
        }
        
        // Parse comma-separated colors or JSON
        if (is_string($colors)) {
            if (strpos($colors, '{') !== false) {
                // JSON format
                $decoded = json_decode($colors, true);
                return is_array($decoded) ? $decoded : [];
            } else {
                // Comma-separated format
                $colorArray = explode(',', $colors);
                return array_map('trim', $colorArray);
            }
        }
        
        return is_array($colors) ? $colors : [];
    }

    /**
     * Check if theme adaptation is enabled
     *
     * @return bool
     */
    public function isThemeAdaptationEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_THEME_ADAPTATION_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get voice loading message
     *
     * @return string
     */
    public function getVoiceLoadingMessage(): string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_VOICE_LOADING_MESSAGE,
            ScopeInterface::SCOPE_STORE
        ) ?? 'Loading voice...';
    }

    /**
     * Get voice ready message
     *
     * @return string
     */
    public function getVoiceReadyMessage(): string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_VOICE_READY_MESSAGE,
            ScopeInterface::SCOPE_STORE
        ) ?? 'Voice ready';
    }

    /**
     * Check if auto-voice for logged users is enabled
     *
     * @return bool
     */
    public function isAutoVoiceForLoggedUsersEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_VOICE_AUTOPLAY_LOGGED_USERS,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Check if smart rotation is enabled
     *
     * @return bool
     */
    public function isSmartRotationEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_SMART_ROTATION_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get suggestions per load
     *
     * @return int
     */
    public function getSuggestionsPerLoad(): int
    {
        return (int) $this->scopeConfig->getValue(
            self::XML_PATH_SUGGESTIONS_PER_LOAD,
            ScopeInterface::SCOPE_STORE
        ) ?? 5;
    }

    /**
     * Check if ProductInfoAgent is enabled
     *
     * @return bool
     */
    public function isProductInfoAgentEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_PRODUCTINFOAGENT_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Check if should show on product pages
     *
     * @return bool
     */
    public function showOnProductPages(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_SHOW_ON_PRODUCT_PAGES,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Check if should show on category pages
     *
     * @return bool
     */
    public function showOnCategoryPages(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_SHOW_ON_CATEGORY_PAGES,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Check if should show on CMS pages
     *
     * @return bool
     */
    public function showOnCmsPages(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_SHOW_ON_CMS_PAGES,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Check if conversation persistence is enabled
     *
     * @return bool
     */
    public function isConversationPersistenceEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_CONVERSATION_PERSISTENCE_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get persistence duration
     *
     * @return int
     */
    public function getPersistenceDuration(): int
    {
        return (int) $this->scopeConfig->getValue(
            self::XML_PATH_PERSISTENCE_DURATION,
            ScopeInterface::SCOPE_STORE
        ) ?? 3600; // Default 1 hour
    }

    /**
     * Get chat display mode
     *
     * @return string
     */
    public function getChatDisplayMode(): string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_CHAT_DISPLAY_MODE,
            ScopeInterface::SCOPE_STORE
        ) ?? 'embedded';
    }

    /**
     * Check if advanced rules are enabled
     *
     * @return bool
     */
    public function isAdvancedRulesEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ADVANCED_RULES_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get selected AI model
     *
     * @return string
     */
    public function getSelectedModel(): string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_SELECTED_MODEL,
            ScopeInterface::SCOPE_STORE
        ) ?? 'gemini';
    }

    /**
     * Get time-based rules
     *
     * @return array
     */
    public function getTimeBasedRules(): array
    {
        $rules = $this->scopeConfig->getValue(
            self::XML_PATH_TIME_BASED_RULES,
            ScopeInterface::SCOPE_STORE
        );
        return is_array($rules) ? $rules : [];
    }

    /**
     * Get customer segment rules
     *
     * @return array
     */
    public function getCustomerSegmentRules(): array
    {
        $rules = $this->scopeConfig->getValue(
            self::XML_PATH_CUSTOMER_SEGMENT_RULES,
            ScopeInterface::SCOPE_STORE
        );
        return is_array($rules) ? $rules : [];
    }

    /**
     * Get category rules
     *
     * @return array
     */
    public function getCategoryRules(): array
    {
        $rules = $this->scopeConfig->getValue(
            self::XML_PATH_CATEGORY_RULES,
            ScopeInterface::SCOPE_STORE
        );
        return is_array($rules) ? $rules : [];
    }

    /**
     * Get product type rules
     *
     * @return array
     */
    public function getProductTypeRules(): array
    {
        $rules = $this->scopeConfig->getValue(
            self::XML_PATH_PRODUCT_TYPE_RULES,
            ScopeInterface::SCOPE_STORE
        );
        return is_array($rules) ? $rules : [];
    }

    /**
     * Get stock rules
     *
     * @return array
     */
    public function getStockRules(): array
    {
        $rules = $this->scopeConfig->getValue(
            self::XML_PATH_STOCK_RULES,
            ScopeInterface::SCOPE_STORE
        );
        return is_array($rules) ? $rules : [];
    }

    /**
     * Get attribute filters
     *
     * @return array
     */
    public function getAttributeFilters(): array
    {
        $filters = $this->scopeConfig->getValue(
            self::XML_PATH_ATTRIBUTE_FILTERS,
            ScopeInterface::SCOPE_STORE
        );
        return is_array($filters) ? $filters : [];
    }

    /**
     * Get Deepgram API key
     *
     * @return string
     */
    public function getDeepgramApiKey(): string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_DEEPGRAM_API_KEY,
            ScopeInterface::SCOPE_STORE
        ) ?? '';
    }

    /**
     * Get voice model
     *
     * @return string
     */
    public function getVoiceModel(): string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_VOICE_MODEL,
            ScopeInterface::SCOPE_STORE
        ) ?? 'nova-2';
    }

    /**
     * Get voice cache lifetime
     *
     * @return int
     */
    public function getVoiceCacheLifetime(): int
    {
        return (int) $this->scopeConfig->getValue(
            self::XML_PATH_VOICE_CACHE_LIFETIME,
            ScopeInterface::SCOPE_STORE
        ) ?? 3600;
    }

    /**
     * Check if guests are allowed
     *
     * @return bool
     */
    public function isGuestAllowed(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_GUEST_ALLOWED,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get system prompt
     *
     * @return string
     */
    public function getSystemPrompt(): string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_SYSTEM_PROMPT,
            ScopeInterface::SCOPE_STORE
        ) ?? 'You are a helpful AI assistant for product information.';
    }

    /**
     * Get suggestions cache lifetime
     *
     * @return int
     */
    public function getSuggestionsCacheLifetime(): int
    {
        return (int) $this->scopeConfig->getValue(
            self::XML_PATH_SUGGESTIONS_CACHE_LIFETIME,
            ScopeInterface::SCOPE_STORE
        ) ?? 3600; // Default 1 hour
    }
}