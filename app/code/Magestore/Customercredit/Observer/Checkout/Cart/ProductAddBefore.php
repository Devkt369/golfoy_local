<?php
/**
 * Magestore
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Magestore.com license that is
 * available through the world-wide-web at this URL:
 * http://www.magestore.com/license-agreement.html
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Magestore
 * @package     Magestore_Customercredit
 * @copyright   Copyright (c) 2017 Magestore (http://www.magestore.com/)
 * @license     http://www.magestore.com/license-agreement.html
 *
 */

namespace Magestore\Customercredit\Observer\Checkout\Cart;

use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Message\ManagerInterface;
use Magestore\Customercredit\Model\Source\Storecredittype;

/**
 * Observer - ProductAddBefore
 */
class ProductAddBefore implements ObserverInterface
{
    /**
     * @var ProductFactory
     */
    protected $_product;
    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;
    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @param ProductFactory $product
     * @param ScopeConfigInterface $scopeConfig
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        ProductFactory $product,
        ScopeConfigInterface $scopeConfig,
        ManagerInterface $messageManager
    ) {
        $this->_product = $product;
        $this->_scopeConfig = $scopeConfig;
        $this->messageManager = $messageManager;
    }

    /**
     * Add custom option for credit product type is fixed when add to card via ajax
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $productId = (int)$observer->getRequest()->getParam('product');
        if ($productId) {
            $product = $this->_product->create()->load($productId);
            if ($product->getTypeId() == 'customercredit') {
                if ($this->_scopeConfig->getValue('customercredit/general/enable') == 0) {
                    $this->messageManager->addError(
                        __("This product cannot be added to your cart. Please try other products.")
                    );
                    $observer->getRequest()->setParam('product', false);
                    $observer->getRequest()->setParam('amount', false);
                }
                $amount = $observer->getRequest()->getParam('amount');
                if (!$amount) {
                    if ($product->getStorecreditType() == Storecredittype::CREDIT_TYPE_RANGE) {
                        $observer->getRequest()->setParam('amount', $product->getStorecreditFrom());
                    } elseif ($product->getStorecreditType() == Storecredittype::CREDIT_TYPE_DROPDOWN) {
                        $values = explode(',', (string)$product->getStorecreditDropdown());
                        if (is_array($values)) {
                            $observer->getRequest()->setParam('amount', $values[0]);
                        } else {
                            $observer->getRequest()->setParam('amount', $values);
                        }
                    } else {
                        $observer->getRequest()->setParam('amount', $product->getStorecreditValue());
                    }
                }
            }
        }
    }
}
