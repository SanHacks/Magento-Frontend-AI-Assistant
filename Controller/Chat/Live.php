<?php declare(strict_types=1);

namespace Gundo\ProductInfoAgent\Controller\Chat;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Gundo\ProductInfoAgent\Model\Chat;
use Gundo\ProductInfoAgent\Model\VoiceRecording;
use Gundo\ProductInfoAgent\Helper\Data as ConfigHelper;
use Magento\Customer\Model\Session as CustomerSession;
use Psr\Log\LoggerInterface;

class Live implements HttpPostActionInterface
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var JsonFactory
     */
    private $jsonFactory;

    /**
     * @var Chat
     */
    private $chatModel;

    /**
     * @var VoiceRecording
     */
    private $voiceRecording;

    /**
     * @var ConfigHelper
     */
    private $configHelper;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param RequestInterface $request
     * @param JsonFactory $jsonFactory
     * @param Chat $chatModel
     * @param VoiceRecording $voiceRecording
     * @param ConfigHelper $configHelper
     * @param CustomerSession $customerSession
     * @param LoggerInterface $logger
     */
    public function __construct(
        RequestInterface $request,
        JsonFactory $jsonFactory,
        Chat $chatModel,
        VoiceRecording $voiceRecording,
        ConfigHelper $configHelper,
        CustomerSession $customerSession,
        LoggerInterface $logger
    ) {
        $this->request = $request;
        $this->jsonFactory = $jsonFactory;
        $this->chatModel = $chatModel;
        $this->voiceRecording = $voiceRecording;
        $this->configHelper = $configHelper;
        $this->customerSession = $customerSession;
        $this->logger = $logger;
    }

    /**
     * Execute live chat processing
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $result = $this->jsonFactory->create();

        try {
            $message = $this->request->getParam('message');
            $audioData = $this->request->getParam('audio_data');
            $productId = (int)$this->request->getParam('product_id');
            $sessionId = $this->request->getParam('session_id');

            // Handle voice input if provided
            if ($audioData && $this->configHelper->isVoiceEnabled()) {
                $speechResult = $this->voiceRecording->speechToText($audioData);
                
                if ($speechResult['success']) {
                    $message = $speechResult['transcript'];
                } else {
                    return $result->setData([
                        'success' => false,
                        'error' => 'Voice processing failed: ' . $speechResult['error']
                    ]);
                }
            }

            if (!$message) {
                return $result->setData([
                    'success' => false,
                    'error' => 'No message or voice input provided'
                ]);
            }

            // Check if user is allowed to chat
            if (!$this->configHelper->isGuestAllowed() && !$this->customerSession->isLoggedIn()) {
                return $result->setData([
                    'success' => false,
                    'error' => 'Please log in to use the chat feature'
                ]);
            }

            // Process the chat message
            $chatResponse = $this->chatModel->sendMessage(
                $message,
                $productId,
                $this->customerSession->getCustomerId(),
                $sessionId
            );

            if (empty($chatResponse) || isset($chatResponse[0]['error'])) {
                return $result->setData([
                    'success' => false,
                    'error' => $chatResponse[0]['error'] ?? 'Chat processing failed'
                ]);
            }

            $response = $chatResponse[0];
            $responseData = [
                'success' => true,
                'response' => $response['message'] ?? $response['response'] ?? 'No response',
                'session_id' => $sessionId,
                'voice_enabled' => $this->configHelper->isVoiceEnabled(),
                'chat_id' => $response['chat_id'] ?? null
            ];

            // Add voice URL if voice is enabled and response is available
            if (isset($response['voice_url']) && $response['voice_url']) {
                $responseData['voice_url'] = $response['voice_url'];
            }

            return $result->setData($responseData);

        } catch (\Exception $e) {
            $this->logger->error('Live Chat controller error: ' . $e->getMessage());
            return $result->setData([
                'success' => false,
                'error' => 'An error occurred while processing your message'
            ]);
        }
    }
} 