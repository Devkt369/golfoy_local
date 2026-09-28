<?php
/**
 *  Copyright © 2018 Magestore. All rights reserved.
 *  See COPYING.txt for license details.
 */

namespace Magestore\ReportSuccess\Ui\Component\Filters\Type;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Ui\Component\Filters\FilterModifier;

/**
 * ReportSuccess - Select
 */
class Select extends \Magento\Ui\Component\Filters\Type\Select
{
    /**
     * @var \Magento\Framework\Module\Manager
     */
    protected $moduleManager;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param \Magento\Framework\Api\FilterBuilder $filterBuilder
     * @param FilterModifier $filterModifier
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param OptionSourceInterface|null $optionsProvider
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        \Magento\Framework\Api\FilterBuilder $filterBuilder,
        FilterModifier $filterModifier,
        \Magento\Framework\Module\Manager $moduleManager,
        OptionSourceInterface $optionsProvider = null,
        array $components = [],
        array $data = []
    ) {
        $this->moduleManager = $moduleManager;
        parent::__construct(
            $context,
            $uiComponentFactory,
            $filterBuilder,
            $filterModifier,
            $optionsProvider,
            $components,
            $data
        );
    }

    /**
     * Prepare component configuration
     *
     * @return void
     */
    public function prepare()
    {
        parent::prepare();
        if (!$this->moduleManager->isEnabled('Magestore_SupplierSuccess')) {
            $this->_data['config']['componentDisabled'] = true;
        } else {
            parent::prepare();
        }
    }
}
