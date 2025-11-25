<?php
/**
 * Base API class for Large Language Model integrations
 *
 * @category Gundo
 * @package  Gundo_ProductInfoAgent
 * @author   Gundo <dev@gundo.com>
 * @license  http://www.opensource.org/licenses/mit-license.html MIT License
 * @link     https://www.gundo.com
 */

namespace Gundo\ProductInfoAgent\Api\Data;

use Gundo\ProductInfoAgent\Logger\Logger as LoggerInterface;
use Gundo\ProductInfoAgent\Helper\Data;

/**
 * Abstract base class for LLM API implementations
 *
 * Provides common functionality for calling various AI models
 *
 * @category Gundo
 * @package  Gundo_ProductInfoAgent
 * @author   Gundo <dev@gundo.com>
 * @license  http://www.opensource.org/licenses/mit-license.html MIT License
 * @link     https://www.gundo.com
 */
abstract class BaseLLMApi
{
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $_logger;

    /**
     * @var Data
     */
    private Data $_configData;

    /**
     * Constructor
     *
     * @param LoggerInterface $logger     Logger instance
     * @param Data            $configData Config data instance
     */
    public function __construct(
        LoggerInterface $logger,
        Data            $configData
    ) {
        $this->_logger = $logger;
        $this->_configData = $configData;
    }

    /**
     * Get HTTP headers
     *
     * @return string[] Array of headers
     */
    public function getHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * @param  string $message
     * @param  array  $productData
     * @return array
     */
    protected function callGeminiModel(string $message, array $productData = []): array
    {
        if (!is_array($productData) && empty($productData)) {
            return [
                'response' => 'I currently do not have product data, at the moment, please try again.',
                'message' => $productData,
            ];
        }

        $productData = json_encode($productData);

        $systemPrompt = $this->_configData->getSystemPrompt() . $productData ?? "
         You are a Online Shopping assistant named ProAgent.
          Answer the user's question with relevant information about the product, including:
          Product Info:";

        $userQuestion = " User question:";

        $payload = [
            "contents" => [
                [
                    "parts" => [
                        [
                            "text" => $systemPrompt . $productData . $userQuestion . $message,
                        ]
                    ]
                ]
            ]
        ];


        $curl = curl_init();

        curl_setopt_array(
            $curl,
            array(
                CURLOPT_URL => 'https://generativelanguage.googleapis.com/v1beta/models/'
                    . $this->_configData->getSelectedModel()
                    . ':generateContent?key='
                    . $this->_configData->getApiKey(),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json'
                )
            )
        );

        $response = curl_exec($curl);

        curl_close($curl);

        $this->_logger->info($response);

        $response = json_decode($response);

        return [
            'response' => $response->candidates[0]->content->parts[0]->text ?? "No response found.",
            'prompt' => json_encode($payload),
            'model' => 'Gemini'
        ];
    }

    /**
     * @param  $message
     * @return array
     */
    protected function callOpenAiModel($message): array
    {
        //TODO::ADD OPENAI Support
        return [];
    }

    /**
     * @param  $message
     * @return array
     */
    protected function callOllamaModel($message): array
    {
        //TODO::ADD Ollama Support
        return [];
    }
}
