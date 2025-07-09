<?php declare(strict_types=1);

namespace Gundo\ProductInfoAgent\Model;

use Exception;
use Gundo\ProductInfoAgent\Api\ChatInterface;
use Gundo\ProductInfoAgent\Helper\CollectAgentData\ApiDataCollection as LargeLanguageModelApi;
use Gundo\ProductInfoAgent\Logger\Logger;
use Gundo\ProductInfoAgent\Model\ProductInfoAgentFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;

class Chat implements ChatInterface
{
    /**
     * @var LargeLanguageModelApi
     */
    private LargeLanguageModelApi $api;

    private ProductRepositoryInterface $productRepository;

    /**
     * @var Logger
     */
    private Logger $logger;

    private ProductInfoAgentFactory $productInfoAgentFactory;
    private EventManager $eventManager;
    private DateTime $date;

    /**
     * @var RequestInterface
     */
    private RequestInterface $request;

    /**
     * @var RemoteAddress
     */
    private RemoteAddress $remoteAddress;

    /**
     * @param Logger $logger
     * @param LargeLanguageModelApi $api
     * @param ProductRepositoryInterface $productRepository
     * @param ProductInfoAgentFactory $productInfoAgentFactory
     * @param EventManager $eventManager
     * @param DateTime $date
     * @param RequestInterface $request
     * @param RemoteAddress $remoteAddress
     */
    public function __construct(
        Logger                     $logger,
        LargeLanguageModelApi      $api,
        ProductRepositoryInterface $productRepository,
        ProductInfoAgentFactory    $productInfoAgentFactory,
        EventManager               $eventManager,
        DateTime                   $date,
        RequestInterface           $request,
        RemoteAddress              $remoteAddress
    ) {
        $this->logger = $logger;
        $this->api = $api;
        $this->productRepository = $productRepository;
        $this->productInfoAgentFactory = $productInfoAgentFactory;
        $this->eventManager = $eventManager;
        $this->date = $date;
        $this->request = $request;
        $this->remoteAddress = $remoteAddress;
    }

    /**
     * @param string $message
     * @param int|null $productId
     * @param int|null $customerId
     * @param string|null $sessionId
     * @return array
     */
    public function sendMessage(string $message, int $productId = null, int $customerId = null, string $sessionId = null): array
    {
        try {
            $productData = '';

            if ($productId) {
                $productData = $this->productData($productId);
            }

            $startTime = microtime(true);
            $response = $this->api->callProductAgent($message, $productData, $customerId);
            $endTime = microtime(true);

            $responseTimeMs = (int)(($endTime - $startTime) * 1000);

            $chatData = [
                'message' => $message,
                'product_id' => $productId,
                'customer_id' => $customerId,
                'session_id' => $sessionId,
                'response' => $response,
                'response_time_ms' => $responseTimeMs
            ];

            $chatId = $this->processConverseData($chatData);
            $response['chat_id'] = $chatId;
            $response['message'] = $response['response'] ?? 'No message content.';

            $this->eventManager->dispatch('product_info_agent_response_event', ['productInfoResponse' => $chatData]);

            return [$response];
        } catch (Exception $e) {
            $this->logger->critical($e);
            return [['error' => $e->getMessage()]];
        }
    }

    /**
     * @param mixed $chatData
     * @return void
     */
    public function processConverseData(array $chatData): int
    {
        if ($chatData) {
            $responseArray = $chatData['response'];
            $responseContent = is_array($responseArray) ? json_encode($responseArray) : $responseArray;

            $model = $this->productInfoAgentFactory->create();
            $model->setMessage($chatData['message']);
            $model->setCreatedAt($this->date->gmtDate());
            $model->setProductId($chatData['product_id']);
            $model->setDataCollection($responseContent);
            $model->setModel($responseArray['model'] ?? 'unknown');
            $model->setUserId($chatData['customer_id']);
            $model->setSessionId($chatData['session_id']);
            $model->setResponseTimeMs($chatData['response_time_ms']);
            $model->setTokensPrompt($responseArray['tokens_prompt'] ?? null);
            $model->setTokensResponse($responseArray['tokens_response'] ?? null);
            $model->save();
            return (int)$model->getId();
        }
        return 0;
    }

    /**
     * @param $productId
     * @return array[]
     * @throws NoSuchEntityException
     */
    public function productData($productId): array
    {
        $product = $this->productRepository->getById($productId);

        return [
            'details' => [
                'description' => $product->getDescription(),
                'product_name' => $product->getName(),
                'price' => "ZAR" . $product->getPrice(),
                'short_description' => $product->getShortDescription(),
                'productId' => $product->getId(),
            ]
        ];
    }
}
