<?php

declare(strict_types=1);

namespace ArtM\CustomerSurvey\Model\Source;

use ArtM\CustomerSurvey\Model\Survey;
use Magento\Framework\Data\OptionSourceInterface;

class SurveyPopupTrigger implements OptionSourceInterface
{
    public function toOptionArray()
    {
        return [
            ['value' => Survey::POPUP_TRIGGER_PAGE_LOAD, 'label' => __('On page load')],
            ['value' => Survey::POPUP_TRIGGER_AFTER_SECONDS, 'label' => __('After X seconds')],
            ['value' => Survey::POPUP_TRIGGER_AFTER_PAGE_VIEWS, 'label' => __('After X page views')],
            ['value' => Survey::POPUP_TRIGGER_ON_LOGIN, 'label' => __('On login')],
            ['value' => Survey::POPUP_TRIGGER_ON_LOGOUT, 'label' => __('On logout')],
            ['value' => Survey::POPUP_TRIGGER_ON_ADD_TO_CART, 'label' => __('On add to cart')],
            ['value' => Survey::POPUP_TRIGGER_ON_CART_PAGE, 'label' => __('On cart page')],
            ['value' => Survey::POPUP_TRIGGER_ON_CHECKOUT_PAGE, 'label' => __('On checkout page')],
            ['value' => Survey::POPUP_TRIGGER_ON_ORDER_SUCCESS, 'label' => __('On order success')],
            ['value' => Survey::POPUP_TRIGGER_EXIT_INTENT, 'label' => __('Exit intent')],
        ];
    }
}