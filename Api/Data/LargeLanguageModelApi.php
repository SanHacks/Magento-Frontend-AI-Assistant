<?php declare(strict_types=1);

namespace Gundo\ProductInfoAgent\Api\Data;

class LargeLanguageModelApi extends BaseLLMApi
{
    /**
     * @param  string     $message
     * @param  array|null $productData
     * @param  string     $model
     * @return array
     */
    public function callModel(string $message,array $productData = null, string $model = ""): array
    {
        return match ($model) {
            "OpenAi" => $this->callOpenAiModel($message),
            "Ollama" => $this->callOllamaModel($message),
            default => $this->callGeminiModel($message, $productData),
        };
    }
}
