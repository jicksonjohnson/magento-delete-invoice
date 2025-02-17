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

namespace HelloMage\DeleteInvoice\Helper;

use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Config\ConfigOptionsListConstants;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\RuntimeException;

/**
 * Class Data
 * @package HelloMage\DeleteInvoice\Helper
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected DeploymentConfig $deploymentConfig;

    /**
     * Data constructor.
     * @param Context $context
     * @param DeploymentConfig $deploymentConfig
     */
    public function __construct(
        Context $context,
        DeploymentConfig $deploymentConfig
    ) {
        parent::__construct($context);
        $this->deploymentConfig = $deploymentConfig;
    }

    /**
     * @param null $name
     * @return bool|string|null
     * @throws FileSystemException
     * @throws RuntimeException
     */
    public function getTableName($name = null)
    {
        if ($name == null) {
            return false;
        }

        $tableName = $name;
        $tablePrefix = (string)$this->deploymentConfig->get(
            ConfigOptionsListConstants::CONFIG_PATH_DB_PREFIX
        );

        if ($tablePrefix) {
            $tableName = $tablePrefix . $name;
        }

        return $tableName;
    }
}
