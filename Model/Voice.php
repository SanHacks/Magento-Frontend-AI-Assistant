<?php declare(strict_types=1);

namespace Gundo\ProductInfoAgent\Model;

use Gundo\ProductInfoAgent\Api\VoiceInterface;
use Gundo\ProductInfoAgent\Helper\Data;
use Gundo\ProductInfoAgent\Logger\Logger;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Customer\Model\Session as CustomerSession;

class Voice implements VoiceInterface
{
    /**
     * @var Data
     */
    private Data $configHelper;

    /**
     * @var Logger
     */
    private Logger $logger;

    /**
     * @var CustomerSession
     */
    private CustomerSession $session;

    /**
     * @var DateTime
     */
    private DateTime $dateTime;

    /**
     * @param Data            $configHelper
     * @param Logger          $logger
     * @param CustomerSession $session
     * @param DateTime        $dateTime
     */
    public function __construct(
        Data $configHelper,
        Logger $logger,
        CustomerSession $session,
        DateTime $dateTime
    ) {
        $this->configHelper = $configHelper;
        $this->logger = $logger;
        $this->session = $session;
        $this->dateTime = $dateTime;
    }

    /**
     * @param  string      $text
     * @param  int|null    $productId
     * @param  string|null $sessionId
     * @return array
     */
    public function generateVoice(string $text, int $productId = null, string $sessionId = null): array
    {
        try {
            if (!$this->configHelper->isVoiceEnabled()) {
                return [
                    'success' => false,
                    'error' => 'Voice feature is disabled'
                ];
            }

            $apiKey = $this->configHelper->getDeepgramApiKey();
            if (!$apiKey) {
                return [
                    'success' => false,
                    'error' => 'Deepgram API key not configured'
                ];
            }

            // Check cache first
            $cacheKey = $this->generateCacheKey($text, $productId, $sessionId);
            $cachedVoice = $this->getCachedVoice($cacheKey);
            
            if ($cachedVoice) {
                return [
                    'success' => true,
                    'audio_data' => $cachedVoice,
                    'from_cache' => true,
                    'text' => $text
                ];
            }

            // Generate new voice
            $voiceModel = $this->configHelper->getVoiceModel();
            $audioData = $this->callDeepgramApi($text, $voiceModel, $apiKey);

            if ($audioData) {
                // Cache the result
                $this->cacheVoice($cacheKey, $audioData);
                
                return [
                    'success' => true,
                    'audio_data' => base64_encode($audioData),
                    'from_cache' => false,
                    'text' => $text,
                    'model' => $voiceModel
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to generate voice audio'
            ];

        } catch (\Exception $e) {
            $this->logger->error('Voice generation error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'An error occurred while generating voice'
            ];
        }
    }

    /**
     * Call Deepgram API to generate voice
     *
     * @param  string $text
     * @param  string $model
     * @param  string $apiKey
     * @return string|false
     */
    private function callDeepgramApi(string $text, string $model, string $apiKey)
    {
        $curl = curl_init();

        curl_setopt_array(
            $curl, [
            CURLOPT_URL => "https://api.deepgram.com/v1/speak?model={$model}",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $text,
            CURLOPT_HTTPHEADER => [
                'Authorization: Token ' . $apiKey,
                'Content-Type: text/plain'
            ],
            ]
        );

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        if ($error) {
            $this->logger->error('Deepgram API cURL error: ' . $error);
            return false;
        }

        if ($httpCode !== 200) {
            $this->logger->error("Deepgram API error: HTTP {$httpCode}, Response: " . $response);
            return false;
        }

        return $response;
    }

    /**
     * Generate cache key for voice data
     *
     * @param  string      $text
     * @param  int|null    $productId
     * @param  string|null $sessionId
     * @return string
     */
    private function generateCacheKey(string $text, ?int $productId, ?string $sessionId): string
    {
        $model = $this->configHelper->getVoiceModel();
        return 'voice_' . md5($text . '_' . $model . '_' . ($productId ?? 0) . '_' . ($sessionId ?? ''));
    }

    /**
     * Get cached voice data
     *
     * @param  string $cacheKey
     * @return string|null
     */
    private function getCachedVoice(string $cacheKey): ?string
    {
        $voiceCache = $this->session->getData('voice_cache') ?? [];
        $cacheLifetime = $this->configHelper->getVoiceCacheLifetime() * 60; // Convert to seconds
        
        if (isset($voiceCache[$cacheKey])) {
            $cachedItem = $voiceCache[$cacheKey];
            $now = $this->dateTime->gmtTimestamp();
            
            if (($now - $cachedItem['timestamp']) < $cacheLifetime) {
                return $cachedItem['data'];
            } else {
                // Remove expired cache
                unset($voiceCache[$cacheKey]);
                $this->session->setData('voice_cache', $voiceCache);
            }
        }
        
        return null;
    }

    /**
     * Cache voice data in session
     *
     * @param  string $cacheKey
     * @param  string $audioData
     * @return void
     */
    private function cacheVoice(string $cacheKey, string $audioData): void
    {
        $voiceCache = $this->session->getData('voice_cache') ?? [];
        
        // Clean old cache entries (keep only last 10 entries to prevent memory issues)
        if (count($voiceCache) >= 10) {
            $voiceCache = array_slice($voiceCache, -9, null, true);
        }
        
        $voiceCache[$cacheKey] = [
            'data' => base64_encode($audioData),
            'timestamp' => $this->dateTime->gmtTimestamp()
        ];
        
        $this->session->setData('voice_cache', $voiceCache);
    }
} 