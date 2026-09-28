<?php

namespace ArtM\Common\Plugin\Block\Checkout;

use Magento\Checkout\Block\Checkout\LayoutProcessor as MageLayoutProcessor;
use Magento\Directory\Model\ResourceModel\Region\CollectionFactory as RegionCollectionFactory;
use Magento\Directory\Model\ResourceModel\Country\CollectionFactory as CountryCollectionFactory;
use Magento\Customer\Model\Session as CustomerSession;

class LayoutProcessor
{
    protected $regionCollectionFactory;
    protected $countryCollectionFactory;
    protected $customerSession;

    public function __construct(
        RegionCollectionFactory $regionCollectionFactory,
        CountryCollectionFactory $countryCollectionFactory,
        CustomerSession $customerSession
    ) {
        $this->regionCollectionFactory = $regionCollectionFactory;
        $this->countryCollectionFactory = $countryCollectionFactory;
        $this->customerSession = $customerSession;
    }
    public function afterProcess(MageLayoutProcessor $subject, $jsLayout)
    {
        
        if (
            isset($jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']
            ['children']['shippingAddress']['children']['shipping-address-fieldset']
            ['children']['telephone'])
        ) {
            $telephoneField = &$jsLayout['components']['checkout']['children']['steps']['children']['shipping-step']
                ['children']['shippingAddress']['children']['shipping-address-fieldset']['children']['telephone'];

            if ($this->customerSession->isLoggedIn()) {
                $customer = $this->customerSession->getCustomer();
                
                // First try your custom phone_number field
                $phoneNumber = $customer->getData('phone_number');

                if (!empty($phoneNumber)) {
                    $telephoneField['value'] = $phoneNumber;
                } else {
                    // Fallback to default shipping address telephone
                    $defaultShipping = $customer->getDefaultShippingAddress();
                    if ($defaultShipping && $defaultShipping->getTelephone()) {
                        $telephoneField['value'] = $defaultShipping->getTelephone();
                    }
                }
            }
        }

        if (isset($jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
            ['payment']['children']['payments-list']['children'])) {

            foreach ($jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
                     ['payment']['children']['payments-list']['children'] as $key => $payment) {

                /* company */
                if (isset($payment['children']['form-fields']['children']['company'])) {

                    $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
                    ['payment']['children']['payments-list']['children'][$key]['children']['form-fields']['children']
                    ['company']['sortOrder'] = 900;
                }
                $method = substr($key, 0, -5);

                /* vat_id */
                $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
                ['payment']['children']['payments-list']['children'][$key]['children']['form-fields']['children']
                ['vat_id'] = [
                    'component' => 'Magento_Ui/js/form/element/abstract',
                    'config' => [
                        'customScope' => 'billingAddress' . $method,
                        'customEntry' => null,
                        'template' => 'ui/form/field',
                        'elementTmpl' => 'ui/form/element/input',
                        'tooltip' => null,
                    ],
                    'dataScope' => 'billingAddress' . $method . '.vat_id',
                    'dataScopePrefix' => 'billingAddress' . $method,
                    'label' => __('GST number'),
                    'provider' => 'checkoutProvider',
                    'sortOrder' => 1000,
                    'validation' => [],
                    'options' => [],
                    'filterBy' => null,
                    'customEntry' => null,
                    'visible' => true,
                ];
            }
        }


        //  GST
        /* config: checkout/options/display_billing_address_on = payment_page */
        if (isset($jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
            ['payment']['children']['afterMethods']['children']['billing-address-form']
        )) {

            /* company */
            if (isset($jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
                ['payment']['children']['afterMethods']['children']['billing-address-form']['children']['form-fields']
                ['children']['company']
            )) {
                $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
                ['payment']['children']['afterMethods']['children']['billing-address-form']['children']['form-fields']
                ['children']['company']['sortOrder'] = 900;
            }

            /* region & region_id */
            // if (isset($jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
            //     ['payment']['children']['afterMethods']['children']['billing-address-form']['children']['form-fields']
            //     ['children']['region'])) {

            //     $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
            //     ['payment']['children']['afterMethods']['children']['billing-address-form']['children']['form-fields']
            //     ['children']['region']['visible'] = false;

            //     $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
            //     ['payment']['children']['afterMethods']['children']['billing-address-form']['children']['form-fields']
            //     ['children']['region']['config']['componentDisabled'] = true;
            // }
            if (isset($jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
                ['payment']['children']['afterMethods']['children']['billing-address-form']['children']['form-fields']
                ['children']['country_id'])) {

                $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
                ['payment']['children']['afterMethods']['children']['billing-address-form']['children']['form-fields']
                ['children']['country_id']['visible'] = true;

                $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
                ['payment']['children']['afterMethods']['children']['billing-address-form']['children']['form-fields']
                ['children']['country_id']['config']['componentDisabled'] = false;
            }

            $method = 'shared';

            /* vat_id */
            $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
            ['payment']['children']['afterMethods']['children']['billing-address-form']['children']['form-fields']
            ['children']['vat_id'] = [
                                        'component' => 'Magento_Ui/js/form/element/abstract',
                                        'config' => [
                                            'customScope' => 'billingAddress' . $method,
                                            'customEntry' => null,
                                            'template' => 'ui/form/field',
                                            'elementTmpl' => 'ui/form/element/input',
                                            'tooltip' => null,
                                        ],
                                        'dataScope' => 'billingAddress' . $method . '.vat_id',
                                        'dataScopePrefix' => 'billingAddress' . $method,
                                        'label' => __('GST number'),
                                        'provider' => 'checkoutProvider',
                                        'sortOrder' => 900,
                                        'validation' => [],
                                        'options' => [],
                                        'filterBy' => null,
                                        'customEntry' => null,
                                        'visible' => true,
                                    ];
        }

        // ZIP  

        if (isset($jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
            ['payment']['children']['payments-list']['children'])) {

            foreach ($jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
                     ['payment']['children']['payments-list']['children'] as $key => $payment) {

                /* postcode */
                if (isset($payment['children']['form-fields']['children']['postcode'])) {

                    $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
                    ['payment']['children']['payments-list']['children'][$key]['children']['form-fields']['children']
                    ['postcode']['sortOrder'] = 70;
                }

                /* postcode & postcode */
                if (isset($payment['children']['form-fields']['children']['postcode'])) {

                    $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
                    ['payment']['children']['payments-list']['children'][$key]['children']['form-fields']['children']
                    ['postcode']['visible'] = false;

                    $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
                    ['payment']['children']['payments-list']['children'][$key]['children']['form-fields']['children']
                    ['postcode']['config']['componentDisabled'] = true;
                }
                if (isset($payment['children']['form-fields']['children']['postcode'])) {

                    $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
                    ['payment']['children']['payments-list']['children'][$key]['children']['form-fields']['children']
                    ['postcode']['visible'] = false;

                    $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
                    ['payment']['children']['payments-list']['children'][$key]['children']['form-fields']['children']
                    ['postcode']['config']['componentDisabled'] = true;
                }

                $method = substr($key, 0, -5);

                /* postcode */
                $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
                ['payment']['children']['payments-list']['children'][$key]['children']['form-fields']['children']
                ['postcode'] = [
                                    'component' => 'Magento_Ui/js/form/element/abstract',
                                    'config' => [
                                        'customScope' => 'billingAddress' . $method,
                                        'customEntry' => null,
                                        'template' => 'ui/form/field',
                                        'elementTmpl' => 'ui/form/element/input',
                                        'tooltip' => null,
                                    ],
                                    'dataScope' => 'billingAddress' . $method . '.postcode',
                                    'dataScopePrefix' => 'billingAddress' . $method,
                                    'label' => __('PIN Code'),
                                    'provider' => 'checkoutProvider',
                                    'sortOrder' => 70,
                                    'validation' => ['required-entry' => true],
                                    'options' => [],
                                    'filterBy' => null,
                                    'customEntry' => null,
                                    'visible' => true,
                                ];
            }
        }


          // ZIP  

          if (isset($jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
                ['payment']['children']['payments-list']['children'])) {

                foreach ($jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
                        ['payment']['children']['payments-list']['children'] as $key => $payment) {

                    /* postcode */
                    if (isset($payment['children']['form-fields']['children']['postcode'])) {

                        $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
                        ['payment']['children']['payments-list']['children'][$key]['children']['form-fields']['children']
                        ['postcode']['sortOrder'] = 70;
                    }

                    /* postcode & postcode */
                    if (isset($payment['children']['form-fields']['children']['postcode'])) {

                        $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
                        ['payment']['children']['payments-list']['children'][$key]['children']['form-fields']['children']
                        ['postcode']['visible'] = false;

                        $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
                        ['payment']['children']['payments-list']['children'][$key]['children']['form-fields']['children']
                        ['postcode']['config']['componentDisabled'] = true;
                    }
                    if (isset($payment['children']['form-fields']['children']['postcode'])) {

                        $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
                        ['payment']['children']['payments-list']['children'][$key]['children']['form-fields']['children']
                        ['postcode']['visible'] = false;

                        $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
                        ['payment']['children']['payments-list']['children'][$key]['children']['form-fields']['children']
                        ['postcode']['config']['componentDisabled'] = true;
                    }

                    $method = substr($key, 0, -5);

                    /* postcode */
                    $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
                    ['payment']['children']['payments-list']['children'][$key]['children']['form-fields']['children']
                    ['postcode'] = [
                        'component' => 'Magento_Ui/js/form/element/abstract',
                        'config' => [
                            'customScope' => 'billingAddress' . $method,
                            'customEntry' => null,
                            'template' => 'ui/form/field',
                            'elementTmpl' => 'ui/form/element/input',
                            'tooltip' => null,
                        ],
                        'dataScope' => 'billingAddress' . $method . '.postcode',
                        'dataScopePrefix' => 'billingAddress' . $method,
                        'label' => __('PIN Code'),
                        'provider' => 'checkoutProvider',
                        'sortOrder' => 70,
                        'validation' => ['required-entry' => true],
                        'options' => [],
                        'filterBy' => null,
                        'customEntry' => null,
                        'visible' => true,
                    ];
                }
            }


       // city  

       if (isset($jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
                ['payment']['children']['payments-list']['children']
            )) {

       foreach ($jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
                ['payment']['children']['payments-list']['children'] as $key => $payment) {

           /* city & city */
           if (isset($payment['children']['form-fields']['children']['city'])) {

               $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
               ['payment']['children']['payments-list']['children'][$key]['children']['form-fields']['children']
               ['city']['visible'] = false;

               $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
               ['payment']['children']['payments-list']['children'][$key]['children']['form-fields']['children']
               ['city']['config']['componentDisabled'] = true;
           }
           if (isset($payment['children']['form-fields']['children']['city'])) {

               $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
               ['payment']['children']['payments-list']['children'][$key]['children']['form-fields']['children']
               ['city']['visible'] = false;

               $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
               ['payment']['children']['payments-list']['children'][$key]['children']['form-fields']['children']
               ['city']['config']['componentDisabled'] = true;
           }

           $method = substr($key, 0, -5);

           /* city */
           $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
           ['payment']['children']['payments-list']['children'][$key]['children']['form-fields']['children']
           ['city']  =  [
                            'component' => 'Magento_Ui/js/form/element/abstract',
                            'config' => [
                                'customScope' => 'billingAddress' . $method,
                                'customEntry' => null,
                                'template' => 'ui/form/field',
                                'elementTmpl' => 'ui/form/element/input',
                                'tooltip' => null,
                            ],
                            'dataScope' => 'billingAddress' . $method . '.city',
                            'dataScopePrefix' => 'billingAddress' . $method,
                            'label' => __('City'),
                            'provider' => 'checkoutProvider',
                            'sortOrder' => 75,
                            'validation' => [],
                            'options' => [],
                            'filterBy' => null,
                            'customEntry' => null,
                            'visible' => true,
                        ];
       }
   }

   if (isset($jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
        ['payment']['children']['payments-list']['children']
        )) {
   // Loop through each payment method
        foreach ($jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
                    ['payment']['children']['payments-list']['children'] as $key => $payment) {

                $method = substr($key, 0, -5);
            // Check if the form-fields container exists
            if (isset($payment['children']['form-fields']['children'])) {
                // Add region_id field with options
                $regions = $this->getRegionOptions();
                $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
                ['payment']['children']['payments-list']['children'][$key]['children']['form-fields']['children']
                ['region_id'] = [
                                    'component' => 'Magento_Ui/js/form/element/select',
                                    'config' => [
                                        'customScope' => 'billingAddress' . $method,
                                        'template' => 'ui/form/field',
                                        'elementTmpl' => 'ui/form/element/select',
                                    ],
                                    'dataScope' => 'billingAddress' . $method . '.region_id',
                                    'label' => __('State'),
                                    'provider' => 'checkoutProvider',
                                    'sortOrder' => 75,
                                    'validation' => ['required-entry' => true],
                                    'options' => $regions,
                                    'visible' => true,
                                ];
            }
        }
    }
   
        
        return $jsLayout;
    }


    protected function getRegionOptions()
    {
        $options =[['label' => 'Select State', 'value' => '']];
        $regions = $this->regionCollectionFactory->create()->addCountryFilter('IN')->toOptionArray();
        foreach ($regions as $region) {
            if ($region['value'] != '') {
                $options[] = [
                    'label' => $region['label'],
                    'value' => $region['value']
                ];
            }
        }
        return $options;
    }
}