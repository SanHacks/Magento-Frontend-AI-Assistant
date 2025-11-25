<?php declare(strict_types=1);

namespace Gundo\ProductInfoAgent\Controller\Image;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Gundo\ProductInfoAgent\Model\ImageProcessor;
use Gundo\ProductInfoAgent\Helper\Data as ConfigHelper;
use Magento\Customer\Model\Session as CustomerSession;
use Psr\Log\LoggerInterface;

class Process implements HttpPostActionInterface
{
    private $request;
    private $jsonFactory;
    private $imageProcessor;
    private $configHelper;
    private $customerSession;
    private $logger;

    public function __construct(
        RequestInterface $request,
        JsonFactory $jsonFactory,
        ImageProcessor $imageProcessor,
        ConfigHelper $configHelper,
        CustomerSession $customerSession,
        LoggerInterface $logger
    ) {
        $this->request = $request;
        $this->jsonFactory = $jsonFactory;
        $this->imageProcessor = $imageProcessor;
        $this->configHelper = $configHelper;
        $this->customerSession = $customerSession;
        $this->logger = $logger;
    }

    public function execute(): ResultInterface
    {
        $result = $this->jsonFactory->create();

        try {
            if (!$this->imageProcessor->isEnabled()) {
                return $result->setData(
                    [
                    'success' => false,
                    'error' => 'Image processing is not enabled'
                    ]
                );
            }

            if (!$this->configHelper->isGuestAllowed() && !$this->customerSession->isLoggedIn()) {
                return $result->setData(
                    [
                    'success' => false,
                    'error' => 'Please log in to use image processing'
                    ]
                );
            }

            $action = $this->request->getParam('action', 'edit');
            $prompt = $this->request->getParam('prompt');

            if (!$prompt) {
                return $result->setData(
                    [
                    'success' => false,
                    'error' => 'Prompt is required'
                    ]
                );
            }

            switch ($action) {
            case 'analyze':
                $imageData = $this->request->getParam('image_data');
                $mimeType = $this->request->getParam('mime_type', 'image/jpeg');

                if (!$imageData) {
                    return $result->setData(
                        [
                        'success' => false,
                        'error' => 'Image data is required for analysis'
                            ]
                    );
                }

                $result_data = $this->imageProcessor->analyzeImage($imageData, $prompt, $mimeType);
                break;

            case 'generate':
                $options = [
                    'size' => $this->request->getParam('size', '1024x1024'),
                    'count' => (int)$this->request->getParam('count', 1),
                    'style' => $this->request->getParam('style', 'realistic')
                ];

                $result_data = $this->imageProcessor->generateImage($prompt, $options);
                break;

            case 'edit':
            default:
                $imageData = $this->request->getParam('image_data');
                $mimeType = $this->request->getParam('mime_type', 'image/jpeg');

                if (!$imageData) {
                    return $result->setData(
                        [
                        'success' => false,
                        'error' => 'Image data is required for editing'
                            ]
                    );
                }

                $result_data = $this->imageProcessor->processImage($imageData, $prompt, $mimeType);
                break;
            }

            return $result->setData($result_data);

        } catch (\Exception $e) {
            $this->logger->error('Image processing error: ' . $e->getMessage());
            return $result->setData(
                [
                'success' => false,
                'error' => 'An error occurred while processing your request'
                ]
            );
        }
    }
} 