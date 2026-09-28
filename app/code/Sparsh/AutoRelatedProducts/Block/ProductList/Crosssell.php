<?php
/**
 * Class Crosssell
 *
 * PHP version 7
 *
 * @category Sparsh
 * @package  Sparsh_AutoRelatedProducts
 * @author   Sparsh <magento@sparsh-technologies.com>
 * @license  https://www.sparsh-technologies.com  Open Software License (OSL 3.0)
 * @link     https://www.sparsh-technologies.com
 */
namespace Sparsh\AutoRelatedProducts\Block\ProductList;

use Magento\CatalogInventory\Helper\Stock as StockHelper;
use Sparsh\AutoRelatedProducts\Ui\Component\Form\DisplayModes;
use Sparsh\AutoRelatedProducts\Ui\Component\Form\Position;
use Sparsh\AutoRelatedProducts\Model\ResourceModel\AutoRelatedRule\CollectionFactory;

/**
 * Class Crosssell
 *
 * @category Sparsh
 * @package  Sparsh_AutoRelatedProducts
 * @author   Sparsh <magento@sparsh-technologies.com>
 * @license  https://www.sparsh-technologies.com  Open Software License (OSL 3.0)
 * @link     https://www.sparsh-technologies.com
 */
class Crosssell extends \Magento\Checkout\Block\Cart\Crosssell
{
    /**
     * @var \Sparsh\AutoRelatedProducts\Model\ResourceModel\AutoRelatedRule\CollectionFactory
     */
    protected $autorelatedCollectionFactory;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Customer\Model\SessionFactory
     */
    protected $customerSession;

    /**
     * Crosssell constructor.
     *
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Catalog\Model\Product\Visibility $productVisibility
     * @param \Magento\Catalog\Model\Product\LinkFactory $productLinkFactory
     * @param \Magento\Quote\Model\Quote\Item\RelatedProducts $itemRelationsList
     * @param StockHelper $stockHelper
     * @param CollectionFactory $autorelatedCollectionFactory
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Customer\Model\SessionFactory $customerSession
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Catalog\Model\Product\Visibility $productVisibility,
        \Magento\Catalog\Model\Product\LinkFactory $productLinkFactory,
        \Magento\Quote\Model\Quote\Item\RelatedProducts $itemRelationsList,
        StockHelper $stockHelper,
        CollectionFactory $autorelatedCollectionFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Model\SessionFactory $customerSession,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $checkoutSession,
            $productVisibility,
            $productLinkFactory,
            $itemRelationsList,
            $stockHelper,
            $data
        );
        $this->autorelatedCollectionFactory = $autorelatedCollectionFactory;
        $this->timezone = $timezone;
        $this->storeManager = $storeManager;
        $this->customerSession = $customerSession;
    }

    /**
     * Return block html
     *
     * @return string
     */
    public function toHtml()
    {
        $ruleCollection = $this->getRulesCollection();
        if (count($ruleCollection) > 0) {
            return $this->getChildHtml();
        } else {
            return parent::toHtml();
        }
    }

    /**
     * Get rules collection
     *
     * @return \Sparsh\AutoRelatedProducts\Model\ResourceModel\AutoRelatedRule\Collection
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getRulesCollection()
    {
        $ruleCollection = $this->autorelatedCollectionFactory->create()->addFieldToFilter('status', 1);
        $currentDate = $this->timezone->date()->format('Y-m-d');
        $ruleCollection->addFieldToFilter('rule_type', 'cart');
        $ruleCollection->addFieldToFilter('display_mode', DisplayModes::BLOCK);
        $ruleCollection->addStoreFilter($this->storeManager->getStore()->getId());
        $ruleCollection->addCustomerGroupFilter($this->getCustomerGroupId());
        $ruleCollection->addFieldToFilter('start_date', [['lteq' => $currentDate], ['null' => true]]);
        $ruleCollection->addFieldToFilter('end_date', [['gteq' => $currentDate], ['null' => true]]);
        $ruleCollection->addFieldToFilter('position', Position::REPLACE_CROSSSELL);
        $ruleCollection->setOrder('priority', 'asc');
        $ruleCollection->getSelect()->group('main_table.rule_id');
        return $ruleCollection;
    }

    /**
     * Return customer group id
     *
     * @return int
     */
    public function getCustomerGroupId()
    {
        if ($this->customerSession->create()->isLoggedIn()) {
            return $this->customerSession->create()->getCustomer()->getGroupId();
        }
        return 0;
    }
}
