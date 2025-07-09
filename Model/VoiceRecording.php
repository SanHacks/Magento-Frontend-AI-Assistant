<?php declare(strict_types=1);

namespace Gundo\ProductInfoAgent\Model;

use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\Serializer\Json;
use Gundo\ProductInfoAgent\Helper\Data as ConfigHelper;
use Psr\Log\LoggerInterface;

class VoiceRecording
{
    /**
     * @var Curl
     */
    private $curl;

    /**
     * @var Json
     */
    private $json;

    /**
     * @var ConfigHelper
     */
    private $configHelper;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Curl $curl
     * @param Json $json
     * @param ConfigHelper $configHelper
     * @param LoggerInterface $logger
     */
    public function __construct(
        Curl $curl,
        Json $json,
        ConfigHelper $configHelper,
        LoggerInterface $logger
    ) {
        $this->curl = $curl;
        $this->json = $json;
        $this->configHelper = $configHelper;
        $this->logger = $logger;
    }

    /**
     * Convert speech to text using Deepgram API
     *
     * @param string $audioData Base64 encoded audio data
     * @param string $mimeType Audio mime type
     * @return array
     */
    public function speechToText(string $audioData, string $mimeType = 'audio/wav'): array
    {
        try {
            $apiKey = $this->configHelper->getDeepgramApiKey();
            if (!$apiKey) {
                throw new \Exception('Deepgram API key not configured');
            }

            // Decode base64 audio data
            $audioContent = base64_decode($audioData);
            if (!$audioContent) {
                throw new \Exception('Invalid audio data');
            }

            // Prepare API request
            $url = 'https://api.deepgram.com/v1/listen?smart_format=true&language=en&model=nova-2';
            
            $this->curl->setHeaders([
                'Authorization: Token ' . $apiKey,
                'Content-Type: ' . $mimeType
            ]);

            $this->curl->post($url, $audioContent);
            $response = $this->curl->getBody();
            $httpCode = $this->curl->getStatus();

            if ($httpCode !== 200) {
                throw new \Exception('Deepgram API error: HTTP ' . $httpCode . ' - ' . $response);
            }

            $result = $this->json->unserialize($response);
            
            if (!isset($result['results']['channels'][0]['alternatives'][0]['transcript'])) {
                throw new \Exception('No transcript found in response');
            }

            $transcript = $result['results']['channels'][0]['alternatives'][0]['transcript'];
            $confidence = $result['results']['channels'][0]['alternatives'][0]['confidence'] ?? 0;

            return [
                'success' => true,
                'transcript' => $transcript,
                'confidence' => $confidence,
                'raw_response' => $result
            ];

        } catch (\Exception $e) {
            $this->logger->error('VoiceRecording speechToText error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Convert audio file URL to text
     *
     * @param string $audioUrl URL to audio file
     * @return array
     */
    public function speechToTextFromUrl(string $audioUrl): array
    {
        try {
            $apiKey = $this->configHelper->getDeepgramApiKey();
            if (!$apiKey) {
                throw new \Exception('Deepgram API key not configured');
            }

            $url = 'https://api.deepgram.com/v1/listen?smart_format=true&language=en&model=nova-2';
            
            $payload = $this->json->serialize(['url' => $audioUrl]);

            $this->curl->setHeaders([
                'Authorization: Token ' . $apiKey,
                'Content-Type: application/json'
            ]);

            $this->curl->post($url, $payload);
            $response = $this->curl->getBody();
            $httpCode = $this->curl->getStatus();

            if ($httpCode !== 200) {
                throw new \Exception('Deepgram API error: HTTP ' . $httpCode . ' - ' . $response);
            }

            $result = $this->json->unserialize($response);
            
            if (!isset($result['results']['channels'][0]['alternatives'][0]['transcript'])) {
                throw new \Exception('No transcript found in response');
            }

            $transcript = $result['results']['channels'][0]['alternatives'][0]['transcript'];
            $confidence = $result['results']['channels'][0]['alternatives'][0]['confidence'] ?? 0;

            return [
                'success' => true,
                'transcript' => $transcript,
                'confidence' => $confidence,
                'raw_response' => $result
            ];

        } catch (\Exception $e) {
            $this->logger->error('VoiceRecording speechToTextFromUrl error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
} 