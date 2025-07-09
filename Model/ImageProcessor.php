<?php declare(strict_types=1);

namespace Gundo\ProductInfoAgent\Model;

use Gundo\Imagine\Helper\Data as ImagineConfig;
use Gundo\ProductInfoAgent\Helper\Data as ConfigHelper;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\File\UploaderFactory;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Driver\File;
use Psr\Log\LoggerInterface;

class ImageProcessor
{
    /**
     * @var ImagineConfig
     */
    private $imagineConfig;

    /**
     * @var ConfigHelper
     */
    private $configHelper;

    /**
     * @var Curl
     */
    private $curl;

    /**
     * @var Json
     */
    private $json;

    /**
     * @var UploaderFactory
     */
    private $uploaderFactory;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var File
     */
    private $fileDriver;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ImagineConfig $imagineConfig
     * @param ConfigHelper $configHelper
     * @param Curl $curl
     * @param Json $json
     * @param UploaderFactory $uploaderFactory
     * @param Filesystem $filesystem
     * @param File $fileDriver
     * @param LoggerInterface $logger
     */
    public function __construct(
        ImagineConfig $imagineConfig,
        ConfigHelper $configHelper,
        Curl $curl,
        Json $json,
        UploaderFactory $uploaderFactory,
        Filesystem $filesystem,
        File $fileDriver,
        LoggerInterface $logger
    ) {
        $this->imagineConfig = $imagineConfig;
        $this->configHelper = $configHelper;
        $this->curl = $curl;
        $this->json = $json;
        $this->uploaderFactory = $uploaderFactory;
        $this->filesystem = $filesystem;
        $this->fileDriver = $fileDriver;
        $this->logger = $logger;
    }

    /**
     * Check if image processing is enabled
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->imagineConfig->isImagineEnabled() && $this->imagineConfig->getApiKey();
    }

    /**
     * Analyze image with AI to understand content and provide insights
     *
     * @param string $imageData Base64 encoded image data
     * @param string $prompt Analysis instructions
     * @param string $mimeType Image mime type
     * @return array
     */
    public function analyzeImage(string $imageData, string $prompt, string $mimeType = 'image/jpeg'): array
    {
        try {
            if (!$this->isEnabled()) {
                return [
                    'success' => false,
                    'error' => 'Image analysis is not enabled'
                ];
            }

            $apiKey = $this->imagineConfig->getApiKey();
            
            // Use GPT-4 Vision API for image analysis
            $url = 'https://api.openai.com/v1/chat/completions';
            
            $payload = [
                'model' => 'gpt-4-vision-preview',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => $this->enhanceAnalysisPrompt($prompt)
                            ],
                            [
                                'type' => 'image_url',
                                'image_url' => [
                                    'url' => "data:{$mimeType};base64,{$imageData}",
                                    'detail' => 'high'
                                ]
                            ]
                        ]
                    ]
                ],
                'max_tokens' => 1000,
                'temperature' => 0.7
            ];

            $this->curl->setHeaders([
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json'
            ]);

            $this->curl->post($url, $this->json->serialize($payload));
            $response = $this->curl->getBody();
            $httpCode = $this->curl->getStatus();

            if ($httpCode !== 200) {
                throw new \Exception('OpenAI API error: HTTP ' . $httpCode . ' - ' . $response);
            }

            $result = $this->json->unserialize($response);
            
            if (!isset($result['choices'][0]['message']['content'])) {
                throw new \Exception('No analysis content returned');
            }

            $analysis = $result['choices'][0]['message']['content'];

            return [
                'success' => true,
                'analysis' => $analysis,
                'usage' => $result['usage'] ?? null
            ];

        } catch (\Exception $e) {
            $this->logger->error('ImageProcessor analyzeImage error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Main entry for image generation/editing, chooses provider based on config
     */
    public function processImage(string $imageData, string $prompt, string $mimeType = 'image/jpeg'): array
    {
        $provider = $this->configHelper->getImageAiProvider();
        switch ($provider) {
            case 'gemini':
                return $this->processWithGemini($imageData, $prompt, $mimeType);
            case 'dalle':
            default:
                return $this->processWithDalle($imageData, $prompt, $mimeType);
        }
    }

    /**
     * Process image using DALL-E API
     *
     * @param string $imageData Base64 encoded image data
     * @param string $prompt Editing instructions
     * @param string $mimeType Image mime type
     * @return array
     */
    private function processWithDalle(string $imageData, string $prompt, string $mimeType): array
    {
        $apiKey = $this->configHelper->getDalleApiKey();
        if (!$apiKey) {
            return ['success' => false, 'error' => 'DALL-E API key is not configured'];
        }
        try {
            // Save uploaded image to temp file
            $imageContent = base64_decode($imageData);
            if (!$imageContent) {
                return ['success' => false, 'error' => 'Invalid image data'];
            }
            $tmpDir = $this->filesystem->getDirectoryWrite(DirectoryList::TMP);
            $tmpFile = 'dalle_' . uniqid() . '.png';
            $tmpPath = $tmpDir->getAbsolutePath($tmpFile);
            $this->fileDriver->filePutContents($tmpPath, $imageContent);

            // Prepare multipart/form-data for DALL-E edit endpoint
            $url = 'https://api.openai.com/v1/images/edits';
            $boundary = uniqid();
            $delimiter = '-------------' . $boundary;
            $eol = "\r\n";
            $data = '';
            // Image file
            $data .= "--$delimiter$eol";
            $data .= 'Content-Disposition: form-data; name="image"; filename="image.png"' . $eol;
            $data .= 'Content-Type: image/png' . $eol . $eol;
            $data .= file_get_contents($tmpPath) . $eol;
            // Prompt
            $data .= "--$delimiter$eol";
            $data .= 'Content-Disposition: form-data; name="prompt"' . $eol . $eol;
            $data .= $prompt . $eol;
            // n
            $data .= "--$delimiter$eol";
            $data .= 'Content-Disposition: form-data; name="n"' . $eol . $eol;
            $data .= '1' . $eol;
            // Size
            $data .= "--$delimiter$eol";
            $data .= 'Content-Disposition: form-data; name="size"' . $eol . $eol;
            $data .= '1024x1024' . $eol;
            $data .= "--$delimiter--$eol";

            $headers = [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: multipart/form-data; boundary=' . $delimiter,
            ];

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            // Clean up temp file
            if ($this->fileDriver->isExists($tmpPath)) {
                $this->fileDriver->deleteFile($tmpPath);
            }

            if ($httpCode !== 200) {
                return ['success' => false, 'error' => 'DALL-E API error: ' . $response];
            }
            $result = json_decode($response, true);
            if (!isset($result['data'][0]['url'])) {
                return ['success' => false, 'error' => 'No image returned from DALL-E'];
            }
            // Download and save the image to pub/media/productinfoagent/
            $imageUrl = $result['data'][0]['url'];
            $mediaDir = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
            $saveDir = 'productinfoagent/';
            $mediaDir->create($saveDir);
            $filename = 'dalle_' . uniqid() . '.png';
            $savePath = $mediaDir->getAbsolutePath($saveDir . $filename);
            $imgData = file_get_contents($imageUrl);
            $this->fileDriver->filePutContents($savePath, $imgData);
            $publicUrl = $this->getMediaUrl($saveDir . $filename);
            return ['success' => true, 'images' => [['url' => $publicUrl]]];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'DALL-E error: ' . $e->getMessage()];
        }
    }

    /**
     * Process image using Gemini API
     *
     * @param string $imageData Base64 encoded image data
     * @param string $prompt Editing instructions
     * @param string $mimeType Image mime type
     * @return array
     */
    private function processWithGemini(string $imageData, string $prompt, string $mimeType): array
    {
        $apiKey = $this->configHelper->getGeminiApiKey();
        if (!$apiKey) {
            return ['success' => false, 'error' => 'Gemini API key is not configured'];
        }
        try {
            // Gemini API expects JSON with base64 image and prompt
            $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro-vision:generateContent?key=' . $apiKey;
            $payload = [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                            [
                                'inline_data' => [
                                    'mime_type' => $mimeType,
                                    'data' => $imageData
                                ]
                            ]
                        ]
                    ]
                ]
            ];
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($httpCode !== 200) {
                return ['success' => false, 'error' => 'Gemini API error: ' . $response];
            }
            $result = json_decode($response, true);
            // Gemini returns text, not an image, so this is for demo; real image generation would use a different endpoint
            $text = $result['candidates'][0]['content']['parts'][0]['text'] ?? null;
            if (!$text) {
                return ['success' => false, 'error' => 'No result from Gemini'];
            }
            return ['success' => true, 'images' => [['url' => '', 'description' => $text]]];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => 'Gemini error: ' . $e->getMessage()];
        }
    }

    /**
     * Generate image from text prompt
     *
     * @param string $prompt Image generation prompt
     * @param array $options Additional options
     * @return array
     */
    public function generateImage(string $prompt, array $options = []): array
    {
        try {
            if (!$this->isEnabled()) {
                return [
                    'success' => false,
                    'error' => 'Image generation is not enabled'
                ];
            }

            $apiKey = $this->imagineConfig->getApiKey();
            $fineTune = $this->imagineConfig->getModelFineTune();

            // Use DALL-E 3 API for better quality and realism
            $url = 'https://api.openai.com/v1/images/generations';
            
            $enhancedPrompt = $this->enhanceGenerationPrompt($prompt, $options['style'] ?? 'realistic');
            
            $payload = [
                'model' => 'dall-e-3',
                'prompt' => $enhancedPrompt . ($fineTune ? ' ' . $fineTune : ''),
                'n' => min($options['count'] ?? 1, 1), // DALL-E 3 only supports n=1
                'size' => $options['size'] ?? '1024x1024',
                'quality' => 'hd',
                'style' => $options['style'] === 'artistic' ? 'vivid' : 'natural',
                'response_format' => 'url'
            ];

            $this->curl->setHeaders([
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json'
            ]);

            $this->curl->post($url, $this->json->serialize($payload));
            $response = $this->curl->getBody();
            $httpCode = $this->curl->getStatus();

            if ($httpCode !== 200) {
                throw new \Exception('OpenAI API error: HTTP ' . $httpCode . ' - ' . $response);
            }

            $result = $this->json->unserialize($response);
            
            if (!isset($result['data']) || empty($result['data'])) {
                throw new \Exception('No images generated');
            }

            return [
                'success' => true,
                'images' => $result['data'],
                'usage' => $result['usage'] ?? null
            ];

        } catch (\Exception $e) {
            $this->logger->error('ImageProcessor generateImage error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Enhance analysis prompt for better insights
     *
     * @param string $prompt Original prompt
     * @return string Enhanced prompt
     */
    private function enhanceAnalysisPrompt(string $prompt): string
    {
        $basePrompt = "You are a professional product analyst and fashion consultant. ";
        
        // Detect if this is a clothing/fashion analysis
        if (stripos($prompt, 'clothing') !== false || 
            stripos($prompt, 'dress') !== false || 
            stripos($prompt, 'fashion') !== false ||
            stripos($prompt, 'style') !== false ||
            stripos($prompt, 'wear') !== false ||
            stripos($prompt, 'outfit') !== false) {
            
            $basePrompt .= "Analyze this clothing/fashion item in detail. Consider: style, fit, color, fabric, occasion suitability, body type compatibility, styling suggestions, and how it might look on different people. ";
        }
        
        // Detect if this is a product/item analysis
        if (stripos($prompt, 'product') !== false || 
            stripos($prompt, 'item') !== false ||
            stripos($prompt, 'features') !== false ||
            stripos($prompt, 'quality') !== false) {
            
            $basePrompt .= "Analyze this product comprehensively. Consider: materials, quality indicators, design features, functionality, value proposition, target audience, and usage scenarios. ";
        }
        
        // Detect if this is a room/space analysis
        if (stripos($prompt, 'room') !== false || 
            stripos($prompt, 'space') !== false ||
            stripos($prompt, 'furniture') !== false ||
            stripos($prompt, 'decor') !== false) {
            
            $basePrompt .= "Analyze this space/room/furniture item. Consider: style, functionality, space utilization, color scheme, design elements, and how it would fit in different interior settings. ";
        }
        
        return $basePrompt . $prompt . " Please provide detailed, actionable insights.";
    }

    /**
     * Enhance editing prompt for more realistic results
     *
     * @param string $prompt Original prompt
     * @return string Enhanced prompt
     */
    private function enhanceEditingPrompt(string $prompt): string
    {
        $basePrompt = "Create a highly realistic and professional image edit. ";
        
        // Detect clothing try-on scenarios
        if (stripos($prompt, 'try on') !== false || 
            stripos($prompt, 'look on me') !== false ||
            stripos($prompt, 'wear') !== false ||
            stripos($prompt, 'fit') !== false) {
            
            $basePrompt .= "Focus on realistic clothing fit, proper proportions, natural draping, and authentic styling. Consider body proportions, fabric behavior, and realistic shadows and lighting. ";
        }
        
        // Detect color change scenarios
        if (stripos($prompt, 'color') !== false || 
            stripos($prompt, 'change') !== false) {
            
            $basePrompt .= "Maintain the original texture, lighting, and material properties while changing colors. Ensure the new colors look natural and realistic on the material. ";
        }
        
        // Detect room/space mockups
        if (stripos($prompt, 'room') !== false || 
            stripos($prompt, 'space') !== false) {
            
            $basePrompt .= "Create a realistic room setting with proper perspective, lighting, and scale. Consider interior design principles and realistic spatial relationships. ";
        }
        
        return $basePrompt . $prompt . " Maintain photorealistic quality and natural appearance.";
    }

    /**
     * Enhance generation prompt for more realistic results
     *
     * @param string $prompt Original prompt
     * @param string $style Style preference
     * @return string Enhanced prompt
     */
    private function enhanceGenerationPrompt(string $prompt, string $style = 'realistic'): string
    {
        $basePrompt = "";
        
        if ($style === 'realistic') {
            $basePrompt = "Create a highly realistic, photographic-quality image. ";
        } elseif ($style === 'artistic') {
            $basePrompt = "Create an artistic, stylized image with creative flair. ";
        } elseif ($style === 'professional') {
            $basePrompt = "Create a professional, commercial-quality image suitable for marketing. ";
        }
        
        // Detect lifestyle scenarios
        if (stripos($prompt, 'lifestyle') !== false || 
            stripos($prompt, 'professional') !== false ||
            stripos($prompt, 'setting') !== false) {
            
            $basePrompt .= "Focus on authentic lifestyle photography with natural lighting, realistic environments, and professional composition. ";
        }
        
        // Detect product showcases
        if (stripos($prompt, 'product') !== false || 
            stripos($prompt, 'showcase') !== false) {
            
            $basePrompt .= "Create a professional product showcase with optimal lighting, clean composition, and commercial appeal. ";
        }
        
        return $basePrompt . $prompt . " Ensure high quality, realistic details, and professional appearance.";
    }

    /**
     * Edit existing image with AI
     *
     * @param string $imagePath Path to the image file
     * @param string $prompt Editing instructions
     * @return array
     */
    private function callImageEditingAPI(string $imagePath, string $prompt): array
    {
        try {
            $apiKey = $this->imagineConfig->getApiKey();
            
            // Use DALL-E 2 image edit endpoint
            $url = 'https://api.openai.com/v1/images/edits';
            
            // Prepare multipart form data
            $postFields = [
                'image' => new \CURLFile($imagePath, 'image/jpeg', 'image.jpg'),
                'prompt' => $prompt,
                'n' => 1,
                'size' => '1024x1024',
                'response_format' => 'url'
            ];

            $this->curl->setHeaders([
                'Authorization: Bearer ' . $apiKey
            ]);

            // Use curl directly for multipart form data
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $apiKey
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200) {
                throw new \Exception('OpenAI API error: HTTP ' . $httpCode . ' - ' . $response);
            }

            $result = $this->json->unserialize($response);
            
            if (!isset($result['data']) || empty($result['data'])) {
                throw new \Exception('No edited images returned');
            }

            return [
                'success' => true,
                'edited_images' => $result['data'],
                'original_prompt' => $prompt
            ];

        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Save processed image to media directory
     *
     * @param string $imageUrl URL of the processed image
     * @param string $fileName Desired filename
     * @return array
     */
    public function saveProcessedImage(string $imageUrl, string $fileName = null): array
    {
        try {
            if (!$fileName) {
                $fileName = 'processed_' . uniqid() . '.jpg';
            }

            // Download image from URL
            $this->curl->get($imageUrl);
            $imageContent = $this->curl->getBody();
            
            if (!$imageContent) {
                throw new \Exception('Failed to download processed image');
            }

            // Save to media directory
            $mediaDir = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
            $imagePath = 'productinfo/processed/' . $fileName;
            $mediaDir->writeFile($imagePath, $imageContent);

            return [
                'success' => true,
                'file_path' => $imagePath,
                'url' => $this->getMediaUrl($imagePath)
            ];

        } catch (\Exception $e) {
            $this->logger->error('ImageProcessor saveProcessedImage error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get media URL for a given path
     *
     * @param string $path
     * @return string
     */
    private function getMediaUrl(string $path): string
    {
        return '/pub/media/' . $path;
    }
} 