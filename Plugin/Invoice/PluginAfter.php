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

namespace HelloMage\DeleteInvoice\Plugin\Invoice;

use HelloMage\DeleteInvoice\Helper\Config as SystemConfig;
use HelloMage\DeleteInvoice\Plugin\PluginAbstract;

use Magento\Authorization\Model\Acl\AclRetriever;
use Magento\Backend\Helper\Data;
use Magento\Backend\Model\Auth\Session;
use Magento\Sales\Block\Adminhtml\Order\Invoice\View;

/**
 * Class PluginAfter
 * @package HelloMage\DeleteInvoice\Plugin\Invoice
 */
class PluginAfter extends PluginAbstract
{
    /**
     * @var Data
     */
    protected $data;

    /**
     * @var SystemConfig
     */
    protected $systemConfig;

    /**
     * PluginAfter constructor.
     * @param AclRetriever $aclRetriever
     * @param Session $authSession
     * @param Data $data
     * @param SystemConfig $systemConfig
     */
    public function __construct(
        AclRetriever $aclRetriever,
        Session $authSession,
        SystemConfig $systemConfig,
        Data $data
    ) {
        parent::__construct($aclRetriever, $authSession, $systemConfig);
        $this->data = $data;
    }

    /**
     * @param View $subject
     * @param $result
     * @return mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function afterGetBackUrl(View $subject, $result)
    {
        if ($this->isAllowedResources()) {
            $params = $subject->getRequest()->getParams();
            $message = __('Are you sure you want to do this?');
            if ($subject->getRequest()->getFullActionName() == 'sales_order_invoice_view') {
                $subject->addButton(
                    'hellomage-delete',
                    [
                        'label' => __('Delete'),
                        'onclick' => 'confirmSetLocation(\'' . $message . '\',\'' . $this->getDeleteUrl($params['invoice_id']) . '\')',
                        'class' => 'hellomage-delete'
                    ],
                    -1
                );
            }
        }

        return $result;
    }

    /**
     * @param string $invoiceId
     * @return mixed
     */
    public function getDeleteUrl($invoiceId)
    {
        return $this->data->getUrl(
            'delete-invoice/delete/invoice',
            [
                'invoice_id' => $invoiceId
            ]
        );
    }
}
