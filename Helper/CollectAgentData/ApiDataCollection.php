<?php declare(strict_types=1);

namespace Gundo\ProductInfoAgent\Helper\CollectAgentData;

use Gundo\ProductInfoAgent\Api\Data\LargeLanguageModelApi as LLModelApi;
use Gundo\ProductInfoAgent\Helper\Data;

class ApiDataCollection
{
    /**
     * @var Data $configData
     */
    protected Data $configData;

    /**
     * @var LLModelApi
     */
    protected LLModelApi $largeLanguageModelApi;

    /**
     * @param Data       $configData
     * @param LLModelApi $largeLanguageModelApi
     */
    public function __construct(
        Data       $configData,
        LLModelApi $largeLanguageModelApi
    ) {
        $this->configData = $configData;
        $this->largeLanguageModelApi = $largeLanguageModelApi;
    }

    /**
     * @param  string $message
     * @param  null   $productDetails
     * @param  null   $customerId
     * @return array
     */
    public function callProductAgent(string $message, $productDetails = null, $customerId = null): array
    {
        $response = [
            "response" => "Sorry, I am currently unable to formulate the response, to your question.",
            "model" => "default"
        ];

        if ($message) {
            $selectedModel = $this->configData->getSelectedModel();
            $response = $this->largeLanguageModelApi->callModel($message, $productDetails, $selectedModel);
        }

        return $response;
    }
}
