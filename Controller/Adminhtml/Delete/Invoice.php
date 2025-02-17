<?php
/**
 * HelloMage
 *
 * Do not edit or add to this file if you wish to upgrade to newer versions in the future.
 * If you wish to customise this module for your needs.
 * Please contact us info@hellomage.com
 *
 * @category   HelloMage
 * @package    HelloMage_DeleteInvoice
 * @copyright  Copyright (C) 2020 HELLOMAGE PVT LTD (https://www.hellomage.com/)
 * @license    https://www.hellomage.com/magento2-osl-3-0-license/
 */

namespace HelloMage\DeleteInvoice\Controller\Adminhtml\Delete;

use HelloMage\DeleteInvoice\Helper\Config as SystemConfig;
use HelloMage\DeleteInvoice\Model\Invoice\Delete;

use Exception;
use Magento\Backend\App\Action;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Class Invoice
 * @package HelloMage\DeleteInvoice\Controller\Adminhtml\Delete
 */
class Invoice extends Action
{
    /**
     * @var InvoiceRepositoryInterface
     */
    protected $invoiceRepository;

    /**
     * @var Delete
     */
    protected $delete;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var SystemConfig
     */
    protected $systemConfig;

    /**
     * Invoice constructor.
     * @param Action\Context $context
     * @param InvoiceRepositoryInterface $invoiceRepository
     * @param LoggerInterface $logger
     * @param Delete $delete
     * @param SystemConfig $systemConfig
     */
    public function __construct(
        Action\Context $context,
        InvoiceRepositoryInterface $invoiceRepository,
        LoggerInterface $logger,
        Delete $delete,
        SystemConfig $systemConfig
    ) {
        $this->invoiceRepository = $invoiceRepository;
        $this->logger = $logger;
        $this->delete = $delete;
        $this->systemConfig = $systemConfig;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Redirect|\Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute()
    {
        $invoiceId = $this->getRequest()->getParam('invoice_id');
        $invoice = $this->invoiceRepository->get($invoiceId);
        $orderId = $invoice->getOrderId();
        $redirect_page = $this->systemConfig->getRedirectPage();
        $is_enabled = $this->systemConfig->IsEnabled();
        $resultRedirect = $this->resultRedirectFactory->create();

        if ($is_enabled) {
            try {
                $this->delete->deleteInvoice($invoiceId);
                $this->messageManager->addSuccessMessage(__('Successfully deleted invoice #%1.', $invoice->getIncrementId()));
                if ($redirect_page == 'order-view') {
                    // redirecting to relative order page
                    $resultRedirect->setPath('sales/order/view', ['order_id' => $orderId]);
                } elseif ($redirect_page == 'invoice-listing') {
                    // redirecting to invoice listing
                    $resultRedirect->setPath('sales/invoice/');
                } else {
                    // redirecting to order listing
                    $resultRedirect->setPath('sales/order');
                }
                return $resultRedirect;
            } catch (Exception $e) {
                $this->messageManager->addErrorMessage(__('Error delete invoice #%1.', $invoice->getIncrementId()));
                // redirecting to invoice view
                $resultRedirect->setPath('sales/invoice/view', ['invoice_id' => $invoiceId]);
                return $resultRedirect;
            }
        } else {
            $this->messageManager->addErrorMessage(__('You are not authorized to delete or delete feature is disabled. please check the ACL and HelloMage Delete Invoice module settings'));
            // redirecting to invoice listing
            $resultRedirect->setPath('sales/invoice/view', ['invoice_id' => $invoiceId]);
            return $resultRedirect;
        }
    }

    /*
     * Check permission via ACL resource
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('HelloMage_DeleteInvoice::delete');
    }
}
