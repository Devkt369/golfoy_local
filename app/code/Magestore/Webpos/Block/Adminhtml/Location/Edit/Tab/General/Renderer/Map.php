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
 * @package     Magestore_Storepickup
 * @copyright   Copyright (c) 2012 Magestore (http://www.magestore.com/)
 * @license     http://www.magestore.com/license-agreement.html
 */

namespace Magestore\Webpos\Block\Adminhtml\Location\Edit\Tab\General\Renderer;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magestore\Webpos\Helper\Data;

/**
 * Model Map
 */
class Map extends Widget implements RendererInterface
{
    protected $_template = 'Magestore_Webpos::location/map.phtml';

    /**
     * @var array
     */
    protected $_locationInputNames = [
        'street' => 'input[name="general_information[address_information][street]"]',
        'city' => 'input[name="general_information[address_information][city]"]',
        'country_id' => 'select[name="general_information[address_information][country_id]"]',
        'region' => 'input[name="general_information[address_information][region]"]',
        'region_id' => 'select[name="general_information[address_information][region_id]"]',
        'postcode' => 'input[name="general_information[address_information][postcode]"]',
        'latitude' => 'input[name="general_information[address_information][latitude]"]',
        'longitude' => 'input[name="general_information[address_information][longitude]"]',
    ];

    /**
     * @var array
     */
    protected $_jsonKeys = [
        'latitude',
        'longitude',
        'zoom_level',
        'marker_icon',
    ];

    /**
     * @var Registry
     */
    protected $_coreRegistry;

    /**
     * @var Data
     */
    protected $dataHelper;

    /**
     * Map constructor.
     *
     * @param Context $context
     * @param Registry $registry
     * @param Data $dataHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        Data $dataHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_coreRegistry = $registry;
        $this->dataHelper = $dataHelper;
    }

    /**
     * Get google api key
     *
     * @return mixed
     */
    public function getGoolgeApiKey()
    {
        return $this->dataHelper->getStoreConfig('webpos/general/google_api_key');
    }

    /**
     * Render form element as HTML.
     *
     * @param AbstractElement $element
     *
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $this->setElement($element);

        return $this->toHtml();
    }

    /**
     * Get registry model
     *
     * @return Location
     */
    public function getRegistryModel()
    {
        return $this->_coreRegistry->registry('current_location');
    }

    /**
     * Get html id prefix
     *
     * @return mixed
     */
    public function getHtmlIdPrefix()
    {
        return $this->getHtmlId();
    }

    /**
     * Get option map json
     *
     * @return bool|string
     * @throws NoSuchEntityException
     */
    public function getOptionMapJson()
    {
        $location = $this->getRegistryModel();
        foreach ($this->_locationInputNames as $key => $input) {
            $location->setData('field_'. $key, $input);
            $this->_jsonKeys[] = 'field_'. $key;
        }
        return $location->toJson($this->_jsonKeys);
    }
}
