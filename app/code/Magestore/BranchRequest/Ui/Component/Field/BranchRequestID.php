<?php
/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magestore\BranchRequest\Ui\Component\Field;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Form\Field;
use Magestore\BranchRequest\Service\BranchRequestService;

/**
 * Class BranchRequest BranchRequestID
 */
class BranchRequestID extends Field
{
    /**
     * @var BranchRequestService
     */
    public $branchRequestService;

    /**
     * BranchRequestID construct
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param BranchRequestService $branchRequestService
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        BranchRequestService $branchRequestService,
        array $components = [],
        array $data = []
    ) {
        $this->branchRequestService = $branchRequestService;

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
        $config['default'] = $this->branchRequestService->generateCode();
        return $config;
    }
}
