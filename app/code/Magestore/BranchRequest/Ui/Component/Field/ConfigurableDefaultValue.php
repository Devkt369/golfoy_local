<?php
/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magestore\BranchRequest\Ui\Component\Field;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Form\Field;

/**
 * Class BranchRequest ConfigurableDefaultValue
 */
class ConfigurableDefaultValue extends Field
{
    /**
     * @var ScopeConfigInterface
     */
    public $scopeConfig;

    /**
     * ConfigurableDefaultValue construct
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        ScopeConfigInterface $scopeConfig,
        array $components = [],
        array $data = []
    ) {
        $this->scopeConfig = $scopeConfig;

        parent::__construct(
            $context,
            $uiComponentFactory,
            $components,
            $data
        );
    }

    /**
     * Get Configuration
     *
     * @return array
     */
    public function getConfiguration()
    {
        $config = parent::getConfiguration();
        $config['default'] = $this->scopeConfig->getValue($config['defaultValueConfigPath']);

        return $config;
    }
}
