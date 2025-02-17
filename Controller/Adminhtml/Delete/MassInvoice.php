<?php
/**
 * HelloMage
 *
 * Do not edit or add to this file if you wish to upgrade to newer versions in the future.
 * If you wish to customise this module for your needs.
 * Please contact us jicksonkoottala@gmail.com
 *
 * @category   HelloMage
 * @package    HelloMage_DeleteInvoice
 * @copyright  Copyright (C) 2020 HELLOMAGE PVT LTD (https://www.hellomage.com/)
 * @license    https://www.hellomage.com/magento2-osl-3-0-license/
 */

declare(strict_types=1);

namespace HelloMage\DeleteInvoice\Controller\Adminhtml\Delete;

use HelloMage\DeleteInvoice\Helper\Config as SystemConfig;
use HelloMage\DeleteInvoice\Model\Invoice\Delete;

use Exception;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\Sales\Api\OrderManagementInterface;


/**
 * Class MassInvoice
 * @package HelloMage\DeleteInvoice\Controller\Adminhtml\Delete
 */
class MassInvoice extends \Magento\Sales\Controller\Adminhtml\Order\AbstractMassAction
{
    protected OrderManagementInterface $orderManagement;

    protected \Magento\Sales\Model\ResourceModel\Order\Invoice\CollectionFactory $invoiceCollectionFactory;

    protected \Magento\Sales\Api\InvoiceRepositoryInterface $invoiceRepository;

    protected Delete $delete;
    protected SystemConfig $systemConfig;

    /**
     * MassInvoice constructor.
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param OrderManagementInterface $orderManagement
     * @param \Magento\Sales\Model\ResourceModel\Order\Invoice\CollectionFactory $invoiceCollectionFactory
     * @param InvoiceRepositoryInterface $invoiceRepository
     * @param Delete $delete
     * @param SystemConfig $systemConfig
     */
    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        OrderManagementInterface $orderManagement,
        \Magento\Sales\Model\ResourceModel\Order\Invoice\CollectionFactory $invoiceCollectionFactory,
        InvoiceRepositoryInterface $invoiceRepository,
        Delete $delete,
        SystemConfig $systemConfig
    ) {
        parent::__construct($context, $filter);
        $this->collectionFactory = $collectionFactory;
        $this->orderManagement = $orderManagement;
        $this->invoiceCollectionFactory = $invoiceCollectionFactory;
        $this->invoiceRepository = $invoiceRepository;
        $this->delete = $delete;
        $this->systemConfig = $systemConfig;
    }

    /**
     * @param AbstractCollection $collection
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Redirect|\Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function massAction(AbstractCollection $collection)
    {
        $params = $this->getRequest()->getParams();
        $selected = [];
        $collectionInvoice = $this->filter->getCollection($this->invoiceCollectionFactory->create());
        $is_enabled = $this->systemConfig->IsEnabled();
        $resultRedirect = $this->resultRedirectFactory->create();

        if ($is_enabled) {
            foreach ($collectionInvoice as $invoice) {
                array_push($selected, $invoice->getId());
            }
            if ($selected) {
                foreach ($selected as $invoiceId) {
                    $invoice = $this->invoiceRepository->get($invoiceId);
                    try {
                        $this->deleteInvoiceByModel($invoiceId);
                        $this->messageManager->addSuccessMessage(__('Successfully deleted invoice #%1.', $invoice->getIncrementId()));
                    } catch (\Exception $e) {
                        $this->messageManager->addErrorMessage(__('Error delete invoice #%1.', $invoice->getIncrementId()));
                    }
                }
            }
            $resultRedirect->setPath('sales/invoice/');
            return $resultRedirect;
        } else {
            $this->messageManager->addErrorMessage(__('You are not authorized to delete or delete feature disabled. please check the ACL and HelloMage Delete invoice module settings'));
            $resultRedirect->setPath('sales/invoice/');
            return $resultRedirect;
        }
    }

    /*
     * Check permission via ACL resource
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('HelloMage_DeleteInvoice::massDelete');
    }

    /**
     * @param $invoiceId
     * @return \Magento\Sales\Model\Order
     * @throws Exception
     */
    protected function deleteInvoiceByModel($invoiceId)
    {
        return $this->delete->deleteInvoice($invoiceId);
    }
}
