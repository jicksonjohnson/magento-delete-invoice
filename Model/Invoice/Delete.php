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

namespace HelloMage\DeleteInvoice\Model\Invoice;

use HelloMage\DeleteInvoice\Helper\Data;
use HelloMage\DeleteInvoice\Mail\SendNotification;

use Exception;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\App\ResourceConnection;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;

/**
 * Class Delete
 * @package HelloMage\DeleteInvoice\Model\Invoice
 */
class Delete
{
    /**
     * @var ResourceConnection
     */
    protected $resource;

    /**
     * @var Data
     */
    protected $data;

    /**
     * @var InvoiceRepositoryInterface
     */
    protected $invoiceRepository;

    /**
     * @var Order
     */
    protected $order;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var SendNotification
     */
    protected $sendNotification;

    /**
     * @var Session
     */
    protected $_authSession;

    /**
     * Delete constructor.
     * @param ResourceConnection $resource
     * @param Data $data
     * @param InvoiceRepositoryInterface $invoiceRepository
     * @param Order $order
     * @param LoggerInterface $logger
     * @param SendNotification $sendNotification
     * @param Session $authSession
     */
    public function __construct(
        ResourceConnection $resource,
        Data $data,
        InvoiceRepositoryInterface $invoiceRepository,
        Order $order,
        LoggerInterface $logger,
        SendNotification $sendNotification,
        Session $authSession
    ) {
        $this->resource = $resource;
        $this->data = $data;
        $this->invoiceRepository = $invoiceRepository;
        $this->order = $order;
        $this->logger = $logger;
        $this->sendNotification = $sendNotification;
        $this->_authSession = $authSession;
    }

    /**
     * @param $invoiceId
     * @return Order
     * @throws \Exception
     */
    public function deleteInvoice($invoiceId)
    {
        $invoice = $this->invoiceRepository->get($invoiceId);
        $orderId = $invoice->getOrder()->getId();
        $order = $this->order->load($orderId);
        $orderItems = $order->getAllItems();
        $invoiceItems = $invoice->getAllItems();

        // revert item in order
        foreach ($orderItems as $item) {
            foreach ($invoiceItems as $invoiceItem) {
                if ($invoiceItem->getOrderItemId() == $item->getItemId()) {
                    $item->setQtyInvoiced($item->getQtyInvoiced() - $invoiceItem->getQty());
                    $item->setTaxInvoiced($item->getTaxInvoiced() - $invoiceItem->getTaxAmount());
                    $item->setBaseTaxInvoiced($item->getBaseTaxInvoiced() - $invoiceItem->getBaseTaxAmount());
                    $item->setDiscountTaxCompensationInvoiced(
                        $item->getDiscountTaxCompensationInvoiced() - $invoiceItem->getDiscountTaxCompensationAmount()
                    );
                    $baseDiscountTaxItem = $item->getBaseDiscountTaxCompensationInvoiced();
                    $baseDiscountTaxInvoice = $invoiceItem->getBaseDiscountTaxCompensationAmount();
                    $item->setBaseDiscountTaxCompensationInvoiced(
                        $baseDiscountTaxItem - $baseDiscountTaxInvoice
                    );

                    $item->setDiscountInvoiced($item->getDiscountInvoiced() - $invoiceItem->getDiscountAmount());
                    $item->setBaseDiscountInvoiced(
                        $item->getBaseDiscountInvoiced() - $invoiceItem->getBaseDiscountAmount()
                    );

                    $item->setRowInvoiced($item->getRowInvoiced() - $invoiceItem->getRowTotal());
                    $item->setBaseRowInvoiced($item->getBaseRowInvoiced() - $invoiceItem->getBaseRowTotal());
                }
            }
        }
        // revert info in order
        $order->setTotalInvoiced($order->getTotalInvoiced() - $invoice->getGrandTotal());
        $order->setBaseTotalInvoiced($order->getBaseTotalInvoiced() - $invoice->getBaseGrandTotal());
        $order->setSubtotalInvoiced($order->getSubtotalInvoiced() - $invoice->getSubtotal());
        $order->setBaseSubtotalInvoiced($order->getBaseSubtotalInvoiced() - $invoice->getBaseSubtotal());
        $order->setTaxInvoiced($order->getTaxInvoiced() - $invoice->getTaxAmount());
        $order->setBaseTaxInvoiced($order->getBaseTaxInvoiced() - $invoice->getBaseTaxAmount());
        $order->setDiscountTaxCompensationInvoiced(
            $order->getDiscountTaxCompensationInvoiced() - $invoice->getDiscountTaxCompensationAmount()
        );
        $order->setBaseDiscountTaxCompensationInvoiced(
            $order->getBaseDiscountTaxCompensationInvoiced() - $invoice->getBaseDiscountTaxCompensationAmount()
        );
        $order->setShippingTaxInvoiced($order->getShippingTaxInvoiced() - $invoice->getShippingTaxAmount());
        $order->setBaseShippingTaxInvoiced($order->getBaseShippingTaxInvoiced() - $invoice->getBaseShippingTaxAmount());
        $order->setShippingInvoiced($order->getShippingInvoiced() - $invoice->getShippingAmount());
        $order->setBaseShippingInvoiced($order->getBaseShippingInvoiced() - $invoice->getBaseShippingAmount());
        $order->setDiscountInvoiced($order->getDiscountInvoiced() - $invoice->getDiscountAmount());
        $order->setBaseDiscountInvoiced($order->getBaseDiscountInvoiced() - $invoice->getBaseDiscountAmount());
        $order->setBaseTotalInvoicedCost($order->getBaseTotalInvoicedCost() - $invoice->getBaseCost());

        if ($invoice->getState() == \Magento\Sales\Model\Order\Invoice::STATE_PAID) {
            $order->setTotalPaid($order->getTotalPaid() - $invoice->getGrandTotal());
            $order->setBaseTotalPaid($order->getBaseTotalPaid() - $invoice->getBaseGrandTotal());
        }

        try {
            $invoiceData = $this->invoiceRepository->get($invoiceId);

            $data_to_send = [
                'invoice_id' => $invoiceData->getEntityId(),
                'order_id' => $invoiceData->getOrderId(),
                'increment_id' => $invoiceData->getIncrementId(),
                'admin_details' => 'ID : ' . $this->_authSession->getUser()->getId() . ' | EMAIL : ' . $this->_authSession->getUser()->getEmail(),
                'deleted_at' => date("Y-m-d h:i:s"),
                'store_id' => $invoiceData->getStoreId()
            ];

            //delete invoice by invoice object
            $this->invoiceRepository->delete($invoiceData);

            if ($order->hasShipments() || $order->hasInvoices() || $order->hasCreditmemos()) {
                $order->setState(Order::STATE_PROCESSING)
                    ->setStatus($order->getConfig()->getStateDefaultStatus(Order::STATE_PROCESSING))
                    ->save();
            } else {
                $order->setState(Order::STATE_NEW)
                    ->setStatus($order->getConfig()->getStateDefaultStatus(Order::STATE_NEW))
                    ->save();
            }

            $this->sendNotification->sendEmail($data_to_send);

        } catch (Exception $exception) {
            $this->logger->critical($exception->getMessage());
        }

        return $order;
    }
}
