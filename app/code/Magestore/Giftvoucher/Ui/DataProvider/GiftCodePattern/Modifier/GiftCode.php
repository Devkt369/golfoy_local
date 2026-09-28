<?php
/**
 * Copyright © 2017 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\Giftvoucher\Ui\DataProvider\GiftCodePattern\Modifier;

use Magento\Framework\Registry;
use Magento\Ui\DataProvider\Modifier\ModifierInterface;
use Magento\Framework\UrlInterface;
use Magento\Ui\Component\Form\Fieldset;
use Magestore\Core\Service\CheckExistDisableTmp;

/**
 * Gift code pattern modifier - GiftCode
 */
class GiftCode implements ModifierInterface
{
    /**
     * @var Registry
     */
    protected $coreRegistry;
    
    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var CheckExistDisableTmp
     */
    protected $checkExistDisableTmp;

    /**
     * GiftCode constructor.
     * @param Registry $coreRegistry
     * @param UrlInterface $urlBuilder
     * @param CheckExistDisableTmp $checkExistDisableTmp
     */
    public function __construct(
        Registry $coreRegistry,
        UrlInterface $urlBuilder,
        CheckExistDisableTmp $checkExistDisableTmp
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->urlBuilder = $urlBuilder;
        $this->checkExistDisableTmp = $checkExistDisableTmp;
    }

    /**
     * @inheritDoc
     */
    public function modifyMeta(array $meta)
    {
        if (!$this->coreRegistry->registry('giftcodepattern_data')->getIsGenerated()) {
            return $meta;
        }
        $meta['giftcode_history'] = [
            'children' => [
                'review_listing' => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'autoRender' => true,
                                'componentType' => 'insertListing',
                                'dataScope' => 'giftcard_pattern_code_listing',
                                'externalProvider' => 'giftcard_pattern_code_listing'
                                    . '.' . 'giftcard_pattern_code_listing_data_source',
                                'ns' => 'giftcard_pattern_code_listing',
                                'render_url' => $this->urlBuilder->getUrl('mui/index/render'),
                                'realTimeLink' => false,
                                'behaviourType' => 'simple',
                                'externalFilterMode' => true,
                                'imports' => [
                                    'templateId' => '${ $.provider }:data.current_template_id',
                                ],
                                'exports' => [
                                    'templateId' => '${ $.externalProvider }:params.current_template_id',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'arguments' => [
                'data' => [
                    'config' => [
                        'label' => __('Generated Gift Codes'),
                        'collapsible' => true,
                        'opened' => false,
                        'componentType' => Fieldset::NAME,
                    ],
                ],
            ],
        ];

        if ($this->checkExistDisableTmp->isExistDisableTmpl()) {
            $meta['giftcode_history']['children']['review_listing']['arguments']
            ['data']['config']['imports']['__disableTmpl']['templateId'] = false;

            $meta['giftcode_history']['children']['review_listing']['arguments']
            ['data']['config']['exports']['__disableTmpl']['templateId'] = false;
        }

        return $meta;
    }
    
    /**
     * @inheritDoc
     */
    public function modifyData(array $data)
    {
        $id = $this->coreRegistry->registry('giftcodepattern_data')->getId();
        $data[$id]['current_template_id'] = $id;
        return $data;
    }
}
