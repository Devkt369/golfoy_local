<?php
namespace WeserveCommerce\AjaxSearch\Block;

use Magento\Framework\View\Element\Template;
use WeserveCommerce\AjaxSearch\Model\Config;
use Magento\Store\Model\StoreManagerInterface;

class Search extends Template
{
    /** @var Config */
    protected $config;

    /** @var StoreManagerInterface */
    protected $storeManager;

    public function __construct(
        Template\Context $context,
        Config $config,
        StoreManagerInterface $storeManager,
        array $data = []
    ) {
        $this->config = $config;
        $this->storeManager = $storeManager;
        parent::__construct($context, $data);
    }

    /**
     * Set the template for rendering the autocomplete UI.
     */
    protected function _toHtml()
    {
        $this->setTemplate('WeserveCommerce_AjaxSearch::search.phtml');
        return parent::_toHtml();
    }

    /**
     * Whether the fast search feature is enabled for the current store.
     */
    public function isEnabled(): bool
    {
        $storeId = $this->storeManager->getStore()->getId();
        return $this->config->isEnabled($storeId);
    }

    /**
     * Returns the configured autocomplete result limit.
     */
    public function getAutocompleteLimit(): int
    {
        $storeId = $this->storeManager->getStore()->getId();
        return $this->config->getAutocompleteLimit($storeId);
    }
}
?>
