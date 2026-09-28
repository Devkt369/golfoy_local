<?php
/**
 * Copyright © 2017 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\ClickAndCollectAdminUi\Model\Validate;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magestore\ClickAndCollect\Helper\Data;

/**
 * ConfigClickAndCollect Model
 *
 * @module   ClickAndCollectAdminUi
 * @author   Magestore Developer
 */
class ConfigClickAndCollect extends Value
{
    /**
     * @var ManagerInterface $_messageManager
     */
    protected $_messageManager;

    /**
     * @var Data $dataHelper
     */
    protected $dataHelper;

    /**
     * Validatepattern constructor.
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param ManagerInterface $messageManager
     * @param Data $dataHelper
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        ManagerInterface $messageManager,
        Data $dataHelper,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->dataHelper = $dataHelper;
        $this->_messageManager = $messageManager;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * @inheritDoc
     */
    public function beforeSave()
    {
        $configClickAndCollect = $this->getValue();
        $apiKey = $this->dataHelper->getGoogleApiKey();
        if ($configClickAndCollect) {
            if (!$apiKey) {
                $this->setValue(0);
                $this->_messageManager->addErrorMessage(
                    __('You must be set Google API Key to enable Click & Collect method.')
                );
            }
        }
        return parent::beforeSave();
    }
}
