<?php declare(strict_types=1);

namespace Gundo\ProductInfoAgent\Controller\Voice;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Gundo\ProductInfoAgent\Model\VoiceRecording;
use Gundo\ProductInfoAgent\Helper\Data as ConfigHelper;
use Psr\Log\LoggerInterface;

class Record implements HttpPostActionInterface
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
     * @var VoiceRecording
     */
    private $voiceRecording;

    /**
     * @var ConfigHelper
     */
    private $configHelper;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param RequestInterface $request
     * @param JsonFactory $jsonFactory
     * @param VoiceRecording $voiceRecording
     * @param ConfigHelper $configHelper
     * @param LoggerInterface $logger
     */
    public function __construct(
        RequestInterface $request,
        JsonFactory $jsonFactory,
        VoiceRecording $voiceRecording,
        ConfigHelper $configHelper,
        LoggerInterface $logger
    ) {
        $this->request = $request;
        $this->jsonFactory = $jsonFactory;
        $this->voiceRecording = $voiceRecording;
        $this->configHelper = $configHelper;
        $this->logger = $logger;
    }

    /**
     * Execute voice recording processing
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $result = $this->jsonFactory->create();

        try {
            // Check if voice is enabled
            if (!$this->configHelper->isVoiceEnabled()) {
                return $result->setData([
                    'success' => false,
                    'error' => 'Voice recording is not enabled'
                ]);
            }

            $audioData = $this->request->getParam('audio_data');
            $mimeType = $this->request->getParam('mime_type', 'audio/wav');

            if (!$audioData) {
                return $result->setData([
                    'success' => false,
                    'error' => 'No audio data provided'
                ]);
            }

            // Process speech to text
            $speechResult = $this->voiceRecording->speechToText($audioData, $mimeType);

            if (!$speechResult['success']) {
                return $result->setData([
                    'success' => false,
                    'error' => $speechResult['error']
                ]);
            }

            return $result->setData([
                'success' => true,
                'transcript' => $speechResult['transcript'],
                'confidence' => $speechResult['confidence']
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Voice Record controller error: ' . $e->getMessage());
            return $result->setData([
                'success' => false,
                'error' => 'An error occurred while processing voice recording'
            ]);
        }
    }
} 