<?php
/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\Webpos\Ui\DataProvider\Pos\Form\Modifier;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Ui\Component\Form;
use Magestore\Core\Service\CheckExistDisableTmp;
use Magestore\Webpos\Model\Pos\Pos;

/**
 * Modifier for section closed session in POS detail page
 */
class ClosedSession extends AbstractModifier
{
    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var Registry
     */
    protected $coreRegistry;

    /**
     * @var Pos
     */
    protected $currentPos;

    /**
     * @var CheckExistDisableTmp
     */
    protected $checkExistDisableTmp;

    protected $groupLabel = 'Closed Session';
    protected $sortOrder = 20;
    protected $groupContainer = 'closed_session';

    /**
     * @var array
     */
    protected $loadedData;

    /**
     * ClosedSession constructor.
     * @param UrlInterface $urlBuilder
     * @param RequestInterface $request
     * @param Registry $registry
     * @param CheckExistDisableTmp $checkExistDisableTmp
     */
    public function __construct(
        UrlInterface $urlBuilder,
        RequestInterface $request,
        Registry $registry,
        CheckExistDisableTmp $checkExistDisableTmp
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->request = $request;
        $this->coreRegistry = $registry;
        $this->checkExistDisableTmp = $checkExistDisableTmp;
    }

    /**
     * Get current pos
     *
     * @return Pos
     */
    public function getCurrentPos()
    {
        if (!$this->currentPos) {
            $this->currentPos = $this->coreRegistry->registry('current_pos');
        }
        return $this->currentPos;
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }
        $this->loadedData = [];
        $pos = $this->getCurrentPos();
        if ($pos) {
            $data = $pos->getData();
            $this->loadedData[$pos->getId()] = $data;
        }
        return $this->loadedData;
    }

    /**
     * Get visible
     *
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getVisible()
    {
        $pos = $this->getCurrentPos();
        if (!$pos || !$pos->getId()) {
            return false;
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function modifyMeta(array $meta)
    {
        $meta = array_replace_recursive(
            $meta,
            [
                $this->groupContainer => [
                    'children' => $this->getChildren(),
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'label' => __($this->groupLabel),
                                'autoRender' => true,
                                'collapsible' => true,
                                'visible' => $this->getVisible(),
                                'opened' => false,
                                'componentType' => Form\Fieldset::NAME,
                                'sortOrder' => $this->sortOrder
                            ],
                        ],
                    ],
                ],
            ]
        );
        return $meta;
    }

    /**
     * Retrieve child meta configuration
     *
     * @return array
     */
    public function getChildren()
    {
        $children = [
            'closed_session' => $this->getClosedSession(),
        ];
        return $children;
    }

    /**
     * Get closed session
     *
     * @return array
     */
    public function getClosedSession()
    {
        $listingTarget = 'webpos_closed_session_listing';
        $data = [
            'arguments' => [
                'data' => [
                    'config' => [
                        'autoRender' => true,
                        'componentType' => 'insertListing',
                        'dataScope' => $listingTarget,
                        'externalProvider' => $listingTarget . '.' . $listingTarget . '_data_source',
                        'ns' => $listingTarget,
                        'render_url' => $this->urlBuilder->getUrl('mui/index/render'),
                        'realTimeLink' => true,
                        'dataLinks' => [
                            'imports' => false,
                            'exports' => true
                        ],
                        'behaviourType' => 'simple',
                        'externalFilterMode' => true,
                        'imports' => [
                            'posId' => '${ $.provider }:data.pos_id',
                        ],
                        'exports' => [
                            'posId' => '${ $.externalProvider }:params.pos_id',
                        ]
                    ],
                ],
            ],
        ];

        if ($this->checkExistDisableTmp->isExistDisableTmpl()) {
            $data['arguments']['data']['config']['imports']['__disableTmpl'] = [
                'posId' => false
            ];

            $data['arguments']['data']['config']['exports']['__disableTmpl'] = [
                'posId' => false
            ];
        }

        return $data;
    }
}
