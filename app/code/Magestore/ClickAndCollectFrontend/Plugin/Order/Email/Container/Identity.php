<?php
declare(strict_types=1);

namespace Magestore\ClickAndCollectFrontend\Plugin\Order\Email\Container;

use Magento\Sales\Model\Order\Email\Container\IdentityInterface;
use Magestore\ClickAndCollectApi\Api\DataProvider\CheckIsClickAndCollectOrderInterface;

/**
 * Class IdentityInterface
 *
 * Used for set new email template when using click and collect as shipping method
 */
class Identity
{
    const NEW_ORDER_TEMPLATE_EMAIL_CLICK_AND_COLLECT = 'sales_email_order_template_click_and_collect';

    const NEW_ORDER_GUEST_TEMPLATE_EMAIL_CLICK_AND_COLLECT = 'sales_email_order_guest_template_click_and_collect';
    /**
     * @var bool
     */
    private $checkIsClickAndCollectOrder;

    /**
     * Identity constructor.
     *
     * @param CheckIsClickAndCollectOrderInterface $checkIsClickAndCollectOrder
     */
    public function __construct(
        CheckIsClickAndCollectOrderInterface $checkIsClickAndCollectOrder
    ) {
        $this->checkIsClickAndCollectOrder = $checkIsClickAndCollectOrder;
    }

    /**
     * Used for new template email when checkout guest with click and collect
     *
     * @param IdentityInterface $identity
     * @param string|void $result
     * @return string|void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetGuestTemplateId(IdentityInterface $identity, $result)
    {
        if ($this->checkIsClickAndCollectOrder->getIsClickAndCollectOrder()) {
            return self::NEW_ORDER_GUEST_TEMPLATE_EMAIL_CLICK_AND_COLLECT;
        }
        return $result;
    }

    /**
     * Used for new template email when checkout with storepickup magestore
     *
     * @param IdentityInterface $identity
     * @param string|void $result
     * @return string|void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetTemplateId(IdentityInterface $identity, $result)
    {
        if ($this->checkIsClickAndCollectOrder->getIsClickAndCollectOrder()) {
            return self::NEW_ORDER_TEMPLATE_EMAIL_CLICK_AND_COLLECT;
        }
        return $result;
    }
}
