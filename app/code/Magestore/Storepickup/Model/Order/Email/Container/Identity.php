<?php
namespace Magestore\Storepickup\Model\Order\Email\Container;

use Magento\Sales\Model\Order\Email\Container\IdentityInterface;
use Magestore\Storepickup\Service\IsStorePickupOrder;
use Magento\Sales\Model\Order\Email\Container\Container;

/**
 * Class IdentityInterface
 *
 * Used for set new email template when using store pickup as shipping method
 */
class Identity
{
    const NEW_ORDER_TEMPLATE_EMAIL_STOREPICKUP = 'sales_email_order_template_storepickup';

    const NEW_ORDER_GUEST_TEMPLATE_EMAIL_STOREPICKUP = 'sales_email_order_guest_template_storepickup';
    /**
     * @var bool
     */
    private $isStorePickupOrder;

    /**
     * Identity constructor.
     *
     * @param IsStorePickupOrder $isStorePickupOrder
     */
    public function __construct(IsStorePickupOrder $isStorePickupOrder)
    {
        $this->isStorePickupOrder = $isStorePickupOrder;
    }

    /**
     * Used for new template email when checkout guest with storepickup magestore
     *
     * @param IdentityInterface $identity
     * @param mixed $result
     * @return string|void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetGuestTemplateId(
        IdentityInterface $identity,
        $result
    ) {
        if ($this->isStorePickupOrder->isStorePickUpOrder()) {
            return self::NEW_ORDER_GUEST_TEMPLATE_EMAIL_STOREPICKUP;
        }
        return $result;
    }

    /**
     * Used for new template email when checkout with storepickup magestore
     *
     * @param IdentityInterface $identity
     * @param mixed $result
     * @return string|void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetTemplateId(
        IdentityInterface $identity,
        $result
    ) {
        if ($this->isStorePickupOrder->isStorePickUpOrder()) {
            return self::NEW_ORDER_TEMPLATE_EMAIL_STOREPICKUP;
        }
        return $result;
    }
}
