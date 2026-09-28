<?php

/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\BranchRequest\Ui\DataProvider\Form\Modifier;

use Magento\Ui\DataProvider\Modifier\ModifierInterface;
use Magento\Framework\UrlInterface;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier as ModifierAbstractModifier;

/**
 * Class BranchRequest AbstractModifier
 *
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
class AbstractModifier extends ModifierAbstractModifier implements ModifierInterface
{
    /**
     * Collapsible
     *
     * @var string
     */
    protected $_collapsible = true;

    /**
     * Group Container
     *
     * @var string
     */
    protected $_visible = true;

    /**
     * Group Container
     *
     * @var string
     */
    protected $_opened = true;

    /**
     * sort Sales
     *
     * @var string
     */
    protected $_sortOrder = '1';

    /**
     * Modifier Config
     *
     * @var array
     */
    protected $_modifierConfig = [];

    /**
     * Group Label
     *
     * @var string
     */
    protected $_groupLabel;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * is required
     *
     * @var boolean
     */
    protected $isRequried = true;

    /**
     * is required
     *
     * @var boolean
     */
    protected $visibleImage;

    const TMPL_INPUT = 'ui/form/element/input';
    const TMPL_TEXTAREA = 'ui/form/element/textarea';
    const TMPL_SELECT = 'ui/form/element/select';
    const TMPL_DATE = 'ui/form/element/date';

    /**
     * AbstractModifier constructor.
     *
     * @param UrlInterface $urlBuilder
     * @param \Magento\Framework\App\RequestInterface $request
     * @param array $modifierConfig
     */
    public function __construct(
        UrlInterface                            $urlBuilder,
        \Magento\Framework\App\RequestInterface $request,
        array                                   $modifierConfig = []
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->request = $request;
        $this->_modifierConfig = array_replace_recursive($this->_modifierConfig, $modifierConfig);
    }

    /**
     * Set Visible
     *
     * @param mixed $visible
     * @return void
     */
    public function setVisible($visible)
    {
        $this->_visible = $visible;
    }

    /**
     * Get Visible
     *
     * @return bool|string
     */
    public function getVisible()
    {
        return $this->_visible;
    }

    /**
     * Set Opened
     *
     * @param mixed $opened
     * @return void
     */
    public function setOpened($opened)
    {
        $this->_opened = $opened;
    }

    /**
     * Get Opened
     *
     * @return bool|string
     */
    public function getOpened()
    {
        return $this->_opened;
    }

    /**
     * Set Collapsible
     *
     * @param mixed $collapsible
     * @return void
     */
    public function setCollapsible($collapsible)
    {
        $this->_collapsible = $collapsible;
    }

    /**
     * Get Collapsible
     *
     * @return bool|string
     */
    public function getCollapsible()
    {
        return $this->_collapsible;
    }

    /**
     * Set Group Label
     *
     * @param mixed $groupLabel
     * @return void
     */
    public function setGroupLabel($groupLabel)
    {
        $this->_groupLabel = $groupLabel;
    }

    /**
     * Get Group Label
     *
     * @return string
     */
    public function getGroupLabel()
    {
        return $this->_groupLabel;
    }

    /**
     * Set Sort Order
     *
     * @param mixed $sortOrder
     * @return void
     */
    public function setSortOrder($sortOrder)
    {
        $this->_sortOrder = $sortOrder;
    }

    /**
     * Get Is Required
     *
     * @return bool
     * @SuppressWarnings(PHPMD)
     */
    public function getIsRequired()
    {
        return $this->isRequried;
    }

    /**
     * Set Is Required
     *
     * @param mixed $isRequired
     * @return void
     */
    public function setIsRequired($isRequired)
    {
        $this->isRequried = $isRequired;
    }

    /**
     * Get Sort Order
     *
     * @return string
     */
    public function getSortOrder()
    {
        return $this->_sortOrder;
    }

    /**
     * Modify Data
     *
     * @param array $data
     * @return array
     */
    public function modifyData(array $data)
    {
        return $data;
    }

    /**
     * Modify Meta
     *
     * @param array $meta
     * @return array
     */
    public function modifyMeta(array $meta)
    {
        return $meta;
    }

    /**
     * Get Modify Tmpl
     *
     * @param mixed $type
     * @return string
     */
    public function getModifyTmpl($type)
    {
        switch ($type) {
            case 'input':
                return static::TMPL_INPUT;
            case 'date':
                return static::TMPL_DATE;
            case 'textarea':
                return static::TMPL_TEXTAREA;
            case 'select':
                return static::TMPL_SELECT;
            default:
                return static::TMPL_INPUT;
        }
    }
}
