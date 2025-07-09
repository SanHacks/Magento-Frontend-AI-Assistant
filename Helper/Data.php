<?php declare(strict_types=1);

namespace Gundo\ProductInfoAgent\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    private const PRODUCTINFOAGENT_GENERAL_SYSTEM_PROMPT = 'productinfoagent/general/system_prompt';
    private const PRODUCTINFOAGENT_AUTH_API_KEY = 'productinfoagent/auth/api_key';
    private const PRODUCTINFOAGENT_GENERAL_API_SECRET = 'productinfoagent/general/api_secret';
    private const PRODUCTINFOAGENT_GENERAL_ALLOW_GUESTS = 'productinfoagent/general/allow_guests';
    private const PRODUCTINFOAGENT_GENERAL_SAVE_TO_CUSTOMER_ACCOUNT = 'productinfoagent/general/save_to_customer_account';
    private const PRODUCTINFOAGENT_GENERAL_SAVE_TO_DATABASE = 'productinfoagent/general/save_to_database';
    private const PRODUCTINFOAGENT_GENERAL_CUSTOMER_GROUPS = 'productinfoagent/general/customer_groups';
    private const PRODUCTINFOAGENT_GENERAL_SELECTED_MODEL = 'productinfoagent/general/selected_model';
    private const PRODUCTINFOAGENT_SUGGESTIONS_CACHE_LIFETIME = 'productinfoagent/suggestions/cache_lifetime';
    private const PRODUCTINFOAGENT_GENERAL_IMAGE_QUALITY = 'productinfoagent/general/image_quality';
    private const PRODUCTINFOAGENT_VOICE_ENABLED = 'productinfoagent/voice/voice_enabled';
    private const PRODUCTINFOAGENT_VOICE_DEEPGRAM_API_KEY = 'productinfoagent/voice/deepgram_api_key';
    private const PRODUCTINFOAGENT_VOICE_MODEL = 'productinfoagent/voice/voice_model';
    private const PRODUCTINFOAGENT_VOICE_CACHE_LIFETIME = 'productinfoagent/voice/voice_cache_lifetime';
    private const PRODUCTINFOAGENT_VOICE_LOADING_MESSAGE = 'productinfoagent/voice/loading_message';
    private const PRODUCTINFOAGENT_VOICE_READY_MESSAGE = 'productinfoagent/voice/ready_message';
    private const PRODUCTINFOAGENT_VOICE_AUTO_PLAY_RESPONSES = 'productinfoagent/voice/auto_play_responses';
    private const PRODUCTINFOAGENT_THEME_ADAPTATION_ENABLED = 'productinfoagent/theme/theme_adaptation_enabled';
    private const PRODUCTINFOAGENT_THEME_USER_MESSAGE_COLOR = 'productinfoagent/theme/user_message_color';
    private const PRODUCTINFOAGENT_THEME_ASSISTANT_MESSAGE_COLOR = 'productinfoagent/theme/assistant_message_color';
    private const PRODUCTINFOAGENT_THEME_SEND_BUTTON_COLOR = 'productinfoagent/theme/send_button_color';
    private const PRODUCTINFOAGENT_THEME_CHAT_CONTAINER_BORDER_COLOR = 'productinfoagent/theme/chat_container_border_color';
    private const PRODUCTINFOAGENT_SUGGESTIONS_RANDOMIZE = 'productinfoagent/suggestions/randomize_suggestions';
    private const PRODUCTINFOAGENT_SUGGESTIONS_PER_LOAD = 'productinfoagent/suggestions/suggestions_per_load';
    private const PRODUCTINFOAGENT_SUGGESTIONS_SMART_ROTATION = 'productinfoagent/suggestions/smart_rotation';
    private const PRODUCTINFOAGENT_SUGGESTIONS_AUTO_VOICE_LOGGED = 'productinfoagent/suggestions/auto_voice_for_logged_users';
    private const PRODUCTINFOAGENT_GENERAL_SHOW_ON_PRODUCT_PAGES = 'productinfoagent/general/show_on_product_pages';
    private const PRODUCTINFOAGENT_GENERAL_SHOW_ON_CATEGORY_PAGES = 'productinfoagent/general/show_on_category_pages';
    private const PRODUCTINFOAGENT_GENERAL_SHOW_ON_CMS_PAGES = 'productinfoagent/general/show_on_cms_pages';
    private const PRODUCTINFOAGENT_GENERAL_CONVERSATION_PERSISTENCE = 'productinfoagent/general/conversation_persistence';
    private const PRODUCTINFOAGENT_GENERAL_PERSISTENCE_DURATION = 'productinfoagent/general/persistence_duration';
    private const PRODUCTINFOAGENT_ADVANCED_RULES_ENABLED = 'productinfoagent/advanced_rules/enable_advanced_rules';
    private const PRODUCTINFOAGENT_ADVANCED_RULES_CATEGORY = 'productinfoagent/advanced_rules/category_rules';
    private const PRODUCTINFOAGENT_ADVANCED_RULES_ATTRIBUTES = 'productinfoagent/advanced_rules/attribute_filters';
    private const PRODUCTINFOAGENT_ADVANCED_RULES_PRODUCT_TYPES = 'productinfoagent/advanced_rules/product_type_rules';
    private const PRODUCTINFOAGENT_ADVANCED_RULES_STOCK = 'productinfoagent/advanced_rules/stock_rules';
    private const PRODUCTINFOAGENT_ADVANCED_RULES_CUSTOMER_SEGMENT = 'productinfoagent/advanced_rules/customer_segment_rules';
    private const PRODUCTINFOAGENT_ADVANCED_RULES_TIME_BASED = 'productinfoagent/advanced_rules/time_based_rules';
    private const PRODUCTINFOAGENT_IMAGE_AI_PROVIDER = 'productinfoagent/image_ai/image_ai_provider';
    private const PRODUCTINFOAGENT_IMAGE_DALLE_API_KEY = 'productinfoagent/image_ai/dalle_api_key';
    private const PRODUCTINFOAGENT_IMAGE_GEMINI_API_KEY = 'productinfoagent/image_ai/gemini_api_key';
    private const PRODUCTINFOAGENT_CHAT_DISPLAY_MODE = 'productinfoagent/general/chat_display_mode';

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * Data constructor.
     *
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Context              $context,
        ScopeConfigInterface $scopeConfig
    ) {
        parent::__construct($context);
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Check if ProductInfoAgent is enabled.
     *
     * @param int|string|null $storeId
     * @return bool
     */
    public function isProductInfoAgentEnabled($storeId = null): bool
    {
        return (bool)$this->scopeConfig->getValue(
            self::PRODUCTINFOAGENT_AUTH_API_KEY,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param string $path
     * @param int|string|null $storeId
     * @return string|null
     */
    private function getValue(string $path, $storeId = null): ?string
    {
        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param int|string|null $storeId
     * @return string|null
     */
    public function getApiKey($storeId = null): ?string
    {
        return $this->getValue(self::PRODUCTINFOAGENT_AUTH_API_KEY, $storeId);
    }

    /**
     * @return string|null
     */
    public function getSystemPrompt(): ?string
    {
        return $this->getValue(self::PRODUCTINFOAGENT_GENERAL_SYSTEM_PROMPT);
    }

    /**
     * @param int|string|null $storeId
     * @return string|null
     */
    public function getSelectedModel($storeId = null): ?string
    {
        return $this->getValue(self::PRODUCTINFOAGENT_GENERAL_SELECTED_MODEL, $storeId);
    }

    /**
     * @param int|string|null $storeId
     * @return string|null
     */
    public function getApiSecret($storeId = null): ?string
    {
        return $this->getValue(self::PRODUCTINFOAGENT_GENERAL_API_SECRET, $storeId);
    }

    /**
     * @param int|string|null $storeId
     * @return bool
     */
    public function isGuestAllowed($storeId = null): bool
    {
        return $this->getFlag(self::PRODUCTINFOAGENT_GENERAL_ALLOW_GUESTS, $storeId);
    }

    /**
     * @param string $path
     * @param int|string|null $storeId
     * @return bool
     */
    private function getFlag(string $path, $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag($path, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param int|string|null $storeId
     * @return array
     */
    public function getCustomerGroups($storeId = null): array
    {
        $customerGroups = $this->scopeConfig->getValue(
            self::PRODUCTINFOAGENT_GENERAL_CUSTOMER_GROUPS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return $customerGroups ? explode(',', $customerGroups) : [];
    }

    /**
     * @param int|string|null $storeId
     * @return int
     */
    public function getSuggestionsCacheLifetime($storeId = null): int
    {
        return (int)$this->getValue(self::PRODUCTINFOAGENT_SUGGESTIONS_CACHE_LIFETIME, $storeId);
    }

    /**
     * @param int|string|null $storeId
     * @return bool
     */
    public function isVoiceEnabled($storeId = null): bool
    {
        return $this->getFlag(self::PRODUCTINFOAGENT_VOICE_ENABLED, $storeId);
    }

    /**
     * @param int|string|null $storeId
     * @return string|null
     */
    public function getDeepgramApiKey($storeId = null): ?string
    {
        return $this->getValue(self::PRODUCTINFOAGENT_VOICE_DEEPGRAM_API_KEY, $storeId);
    }

    /**
     * @param int|string|null $storeId
     * @return string|null
     */
    public function getVoiceModel($storeId = null): ?string
    {
        return $this->getValue(self::PRODUCTINFOAGENT_VOICE_MODEL, $storeId) ?: 'aura-2-thalia-en';
    }

    /**
     * @param int|string|null $storeId
     * @return int
     */
    public function getVoiceCacheLifetime($storeId = null): int
    {
        return (int)$this->getValue(self::PRODUCTINFOAGENT_VOICE_CACHE_LIFETIME, $storeId) ?: 30;
    }

    /**
     * @param int|string|null $storeId
     * @return string
     */
    public function getVoiceLoadingMessage($storeId = null): string
    {
        return $this->getValue(self::PRODUCTINFOAGENT_VOICE_LOADING_MESSAGE, $storeId) ?: 'Breathing...';
    }

    /**
     * @param int|string|null $storeId
     * @return string
     */
    public function getVoiceReadyMessage($storeId = null): string
    {
        return $this->getValue(self::PRODUCTINFOAGENT_VOICE_READY_MESSAGE, $storeId) ?: "I'm going to say out loud for you";
    }

    /**
     * Check if auto-play voice responses is enabled
     *
     * @param int|string|null $storeId
     * @return bool
     */
    public function isAutoPlayVoiceEnabled($storeId = null): bool
    {
        return $this->getFlag(self::PRODUCTINFOAGENT_VOICE_AUTO_PLAY_RESPONSES, $storeId);
    }

    /**
     * @param int|string|null $storeId
     * @return bool
     */
    public function isThemeAdaptationEnabled($storeId = null): bool
    {
        return $this->getFlag(self::PRODUCTINFOAGENT_THEME_ADAPTATION_ENABLED, $storeId);
    }

    /**
     * @param int|string|null $storeId
     * @return string|null
     */
    public function getUserMessageColor($storeId = null): ?string
    {
        return $this->getValue(self::PRODUCTINFOAGENT_THEME_USER_MESSAGE_COLOR, $storeId) ?: '#007BFF';
    }

    /**
     * @param int|string|null $storeId
     * @return string|null
     */
    public function getAssistantMessageColor($storeId = null): ?string
    {
        return $this->getValue(self::PRODUCTINFOAGENT_THEME_ASSISTANT_MESSAGE_COLOR, $storeId) ?: '#f0f0f0';
    }

    /**
     * @param int|string|null $storeId
     * @return string|null
     */
    public function getSendButtonColor($storeId = null): ?string
    {
        return $this->getValue(self::PRODUCTINFOAGENT_THEME_SEND_BUTTON_COLOR, $storeId) ?: '#007BFF';
    }

    /**
     * @param int|string|null $storeId
     * @return string|null
     */
    public function getChatContainerBorderColor($storeId = null): ?string
    {
        return $this->getValue(self::PRODUCTINFOAGENT_THEME_CHAT_CONTAINER_BORDER_COLOR, $storeId) ?: '#e0e0e0';
    }

    /**
     * Get all theme colors as array
     * 
     * @param int|string|null $storeId
     * @return array
     */
    public function getThemeColors($storeId = null): array
    {
        if (!$this->isThemeAdaptationEnabled($storeId)) {
            return [];
        }

        return [
            'userMessageColor' => $this->getUserMessageColor($storeId),
            'assistantMessageColor' => $this->getAssistantMessageColor($storeId),
            'sendButtonColor' => $this->getSendButtonColor($storeId),
            'chatContainerBorderColor' => $this->getChatContainerBorderColor($storeId)
        ];
    }

    /**
     * Check if suggestion randomization is enabled
     *
     * @param int|string|null $storeId
     * @return bool
     */
    public function isRandomizeSuggestionsEnabled($storeId = null): bool
    {
        return $this->getFlag(self::PRODUCTINFOAGENT_SUGGESTIONS_RANDOMIZE, $storeId);
    }

    /**
     * Get number of suggestions to show per load
     *
     * @param int|string|null $storeId
     * @return int
     */
    public function getSuggestionsPerLoad($storeId = null): int
    {
        return (int)$this->getValue(self::PRODUCTINFOAGENT_SUGGESTIONS_PER_LOAD, $storeId) ?: 4;
    }

    /**
     * Check if smart suggestion rotation is enabled
     *
     * @param int|string|null $storeId
     * @return bool
     */
    public function isSmartRotationEnabled($storeId = null): bool
    {
        return $this->getFlag(self::PRODUCTINFOAGENT_SUGGESTIONS_SMART_ROTATION, $storeId);
    }

    /**
     * Check if auto voice for logged users is enabled
     *
     * @param int|string|null $storeId
     * @return bool
     */
    public function isAutoVoiceForLoggedUsersEnabled($storeId = null): bool
    {
        return $this->getFlag(self::PRODUCTINFOAGENT_SUGGESTIONS_AUTO_VOICE_LOGGED, $storeId);
    }

    /**
     * Check if chat should show on product detail pages
     *
     * @param int|string|null $storeId
     * @return bool
     */
    public function showOnProductPages($storeId = null): bool
    {
        return $this->getFlag(self::PRODUCTINFOAGENT_GENERAL_SHOW_ON_PRODUCT_PAGES, $storeId);
    }

    /**
     * Check if chat should show on category/listing pages
     *
     * @param int|string|null $storeId
     * @return bool
     */
    public function showOnCategoryPages($storeId = null): bool
    {
        return $this->getFlag(self::PRODUCTINFOAGENT_GENERAL_SHOW_ON_CATEGORY_PAGES, $storeId);
    }

    /**
     * Check if chat should show on CMS pages
     *
     * @param int|string|null $storeId
     * @return bool
     */
    public function showOnCmsPages($storeId = null): bool
    {
        return $this->getFlag(self::PRODUCTINFOAGENT_GENERAL_SHOW_ON_CMS_PAGES, $storeId);
    }

    /**
     * Check if conversation persistence is enabled
     *
     * @param int|string|null $storeId
     * @return bool
     */
    public function isConversationPersistenceEnabled($storeId = null): bool
    {
        return $this->getFlag(self::PRODUCTINFOAGENT_GENERAL_CONVERSATION_PERSISTENCE, $storeId);
    }

    /**
     * Get conversation persistence duration in hours
     *
     * @param int|string|null $storeId
     * @return int
     */
    public function getPersistenceDuration($storeId = null): int
    {
        return (int)$this->getValue(self::PRODUCTINFOAGENT_GENERAL_PERSISTENCE_DURATION, $storeId) ?: 24;
    }

    /**
     * Check if advanced rules are enabled
     *
     * @param int|string|null $storeId
     * @return bool
     */
    public function isAdvancedRulesEnabled($storeId = null): bool
    {
        return $this->getFlag(self::PRODUCTINFOAGENT_ADVANCED_RULES_ENABLED, $storeId);
    }

    /**
     * Get category rules configuration
     *
     * @param int|string|null $storeId
     * @return array
     */
    public function getCategoryRules($storeId = null): array
    {
        $rules = $this->getValue(self::PRODUCTINFOAGENT_ADVANCED_RULES_CATEGORY, $storeId);
        try {
            return $rules ? json_decode($rules, true) ?: [] : [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get attribute filters configuration
     *
     * @param int|string|null $storeId
     * @return array
     */
    public function getAttributeFilters($storeId = null): array
    {
        $filters = $this->getValue(self::PRODUCTINFOAGENT_ADVANCED_RULES_ATTRIBUTES, $storeId);
        try {
            return $filters ? json_decode($filters, true) ?: [] : [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get product type rules
     *
     * @param int|string|null $storeId
     * @return array
     */
    public function getProductTypeRules($storeId = null): array
    {
        $types = $this->getValue(self::PRODUCTINFOAGENT_ADVANCED_RULES_PRODUCT_TYPES, $storeId);
        return $types ? explode(',', $types) : [];
    }

    /**
     * Get stock rules configuration
     *
     * @param int|string|null $storeId
     * @return string
     */
    public function getStockRules($storeId = null): string
    {
        return $this->getValue(self::PRODUCTINFOAGENT_ADVANCED_RULES_STOCK, $storeId) ?: 'all';
    }

    /**
     * Get customer segment rules configuration
     *
     * @param int|string|null $storeId
     * @return array
     */
    public function getCustomerSegmentRules($storeId = null): array
    {
        $rules = $this->getValue(self::PRODUCTINFOAGENT_ADVANCED_RULES_CUSTOMER_SEGMENT, $storeId);
        try {
            return $rules ? json_decode($rules, true) ?: [] : [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get time-based rules configuration
     *
     * @param int|string|null $storeId
     * @return array
     */
    public function getTimeBasedRules($storeId = null): array
    {
        $rules = $this->getValue(self::PRODUCTINFOAGENT_ADVANCED_RULES_TIME_BASED, $storeId);
        try {
            return $rules ? json_decode($rules, true) ?: [] : [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get selected image AI provider
     */
    public function getImageAiProvider($storeId = null): ?string
    {
        return $this->getValue(self::PRODUCTINFOAGENT_IMAGE_AI_PROVIDER, $storeId);
    }

    /**
     * Get DALL-E API key
     */
    public function getDalleApiKey($storeId = null): ?string
    {
        return $this->getValue(self::PRODUCTINFOAGENT_IMAGE_DALLE_API_KEY, $storeId);
    }

    /**
     * Get Gemini API key
     */
    public function getGeminiApiKey($storeId = null): ?string
    {
        return $this->getValue(self::PRODUCTINFOAGENT_IMAGE_GEMINI_API_KEY, $storeId);
    }

    /**
     * Get chat display mode (embedded, stick, popout, nochange)
     */
    public function getChatDisplayMode($storeId = null): string
    {
        return $this->getValue(self::PRODUCTINFOAGENT_CHAT_DISPLAY_MODE, $storeId) ?: 'embedded';
    }
}
