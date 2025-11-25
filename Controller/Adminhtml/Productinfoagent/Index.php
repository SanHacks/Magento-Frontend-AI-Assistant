<?php declare(strict_types=1);

namespace Gundo\ProductInfoAgent\Controller\Adminhtml\Productinfoagent;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

/**
 * Controller for the 'productinfoagent/productinfoagent/index' URL route.
 */
class Index extends Action implements HttpGetActionInterface
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
     * @param PageFactory    $resultPageFactory
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
        $resultPage->setActiveMenu('Gundo_ProductInfoAgent::productinfoagent');
        $resultPage->getConfig()->getTitle()->prepend(__('Product Info Agent'));

        return $resultPage;
    }
}
