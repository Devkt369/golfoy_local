<?php
namespace ArtM\AdminAI\Block\Adminhtml;

use ArtM\AdminAI\Model\IntentProcessor;
use Magento\Backend\Block\Template;

class Chat extends Template
{
    /**
     * @var IntentProcessor
     */
    private $intentProcessor;

    /**
     * @param Template\Context $context
     * @param IntentProcessor $intentProcessor
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        IntentProcessor $intentProcessor,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->intentProcessor = $intentProcessor;
    }

    /**
     * @return array
     */
    public function getSupportedIntents(): array
    {
        return $this->intentProcessor->getSupportedIntents();
    }

    /**
     * @return string
     */
    public function getAskUrl(): string
    {
        return $this->getUrl('adminai/chat/ask');
    }

    /**
     * @return string
     */
    public function getSessionsUrl(): string
    {
        return $this->getUrl('adminai/chat/sessions');
    }

    /**
     * @return string
     */
    public function getMessagesUrl(): string
    {
        return $this->getUrl('adminai/chat/messages');
    }

    /**
     * @return string
     */
    public function getCreateSessionUrl(): string
    {
        return $this->getUrl('adminai/chat/createSession');
    }

    /**
     * @return string
     */
    public function getRenameSessionUrl(): string
    {
        return $this->getUrl('adminai/chat/renameSession');
    }

    /**
     * @return string
     */
    public function getAttributesUrl(): string
    {
        return $this->getUrl('adminai/chat/attributes');
    }

    /**
     * @return string
     */
    public function getCustomerEditUrlTemplate(): string
    {
        return $this->getUrl('customer/index/edit', ['id' => '__id__']);
    }

    /**
     * @return string
     */
    public function getProductEditUrlTemplate(): string
    {
        return $this->getUrl('catalog/product/edit', ['id' => '__id__']);
    }

    /**
     * @return string
     */
    public function getOrderViewUrlTemplate(): string
    {
        return $this->getUrl('sales/order/view', ['order_id' => '__id__']);
    }

    /**
     * @return string
     */
    public function getOrdersGridUrl(): string
    {
        return $this->getUrl('sales/order/index');
    }
}
