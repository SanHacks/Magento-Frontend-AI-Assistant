<?php declare(strict_types=1);

namespace Gundo\ProductInfoAgent\Controller\Adminhtml\Productinfoagent;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

/**
 * Controller for the chat history page.
 */
class History extends Action implements HttpGetActionInterface
{
    /**
     * Authorization level of a basic admin session.
     */
    const ADMIN_RESOURCE = 'Gundo_ProductInfoAgent::productinfoagent';

    /**
     * @var PageFactory
     */
    protected PageFactory $resultPageFactory;

    /**
     * @param Action\Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Action\Context $context,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * @return Page|ResultInterface|ResponseInterface
     */
    public function execute(): Page|ResultInterface|ResponseInterface
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Gundo_ProductInfoAgent::productinfoagent_history');
        $resultPage->getConfig()->getTitle()->prepend(__('Chat History'));

        return $resultPage;
    }
} 