<?php
/**
 * Copyright © 2017 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\Giftvoucher\Ui\DataProvider\Giftvoucher\Modifier;

use Magento\Framework\Registry;
use Magento\Ui\DataProvider\Modifier\ModifierInterface;
use Magento\Framework\UrlInterface;
use Magento\Ui\Component\Form\Field;
use Magestore\Core\Service\CheckExistDisableTmp;
use Magestore\Giftvoucher\Model\Giftvoucher;
use Magestore\Giftvoucher\Model\ResourceModel\GiftTemplate\Collection;
use Magestore\Giftvoucher\Model\Source\Giftvoucher\TemplatesOptionsFactory;

/**
 * Gift voucher modifier - Template
 */
class Template implements ModifierInterface
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
     * @var TemplatesOptionsFactory
     */
    protected $templatesOptionsFactory;
    
    /**
     * @var Collection
     */
    private $templates;

    /**
     * @var CheckExistDisableTmp
     */
    protected $checkExistDisableTmp;

    /**
     * Template constructor.
     * @param Registry $coreRegistry
     * @param UrlInterface $urlBuilder
     * @param TemplatesOptionsFactory $templatesOptionsFactory
     * @param Collection $templates
     * @param CheckExistDisableTmp $checkExistDisableTmp
     */
    public function __construct(
        Registry $coreRegistry,
        UrlInterface $urlBuilder,
        TemplatesOptionsFactory $templatesOptionsFactory,
        Collection $templates,
        CheckExistDisableTmp $checkExistDisableTmp
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->urlBuilder = $urlBuilder;
        $this->templatesOptionsFactory = $templatesOptionsFactory;
        $this->templates = $templates;
        $this->checkExistDisableTmp = $checkExistDisableTmp;
    }

    /**
     * @inheritDoc
     */
    public function modifyMeta(array $meta)
    {
        $fields = [];
        /** @var Giftvoucher $giftvoucher */
        $giftvoucher = $this->coreRegistry->registry('giftvoucher_data');
        
        if ($giftvoucher->getGiftcardCustomImage()) {
            // Show Custom Image
            $fields['giftcard_template_image'] = [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'componentType' => Field::NAME,
                            'dataType' => 'text',
                            'source' => 'giftcard_code',
                            'formElement' => 'input',
                            'component' => 'Magestore_Giftvoucher/js/form/element/image-preview',
                            'label' => __('Customer\'s Image'),
                            'dataScope' => 'giftcard_template_image',
                            'mediaUrl' => $this->urlBuilder->getBaseUrl(['_type' => UrlInterface::URL_TYPE_MEDIA])
                                . 'giftvoucher/template/images/',
                            'sortOrder' => 420,
                        ],
                    ],
                ],
            ];
        } else {
            $fields['giftcard_template_id'] = [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'componentType' => Field::NAME,
                            'dataType' => 'text',
                            'source' => 'giftcard_code',
                            'formElement' => 'select',
                            'label' => __('Template'),
                            'dataScope' => 'giftcard_template_id',
                            'sortOrder' => 420,
                        ],
                        'options' => $this->templatesOptionsFactory->create()->toOptionArray(),
                    ],
                ],
            ];
            $fields['giftcard_template_image'] = [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'componentType' => Field::NAME,
                            'dataType' => 'text',
                            'source' => 'giftcard_code',
                            'formElement' => 'input',
                            'component' => 'Magestore_Giftvoucher/js/form/element/image-select',
                            'label' => __('Template image'),
                            'dataScope' => 'giftcard_template_image',
                            'mediaUrl' => $this->urlBuilder->getBaseUrl(['_type' => UrlInterface::URL_TYPE_MEDIA])
                                . 'giftvoucher/template/images/',
                            'templateImages' => $this->getTemplateImages(),
                            'imports' => [
                                'templateId' => '${ $.parentName }.giftcard_template_id:value',
                            ],
                            'sortOrder' => 430,
                        ],
                    ],
                ],
            ];

            if ($this->checkExistDisableTmp->isExistDisableTmpl()) {
                $fields['giftcard_template_image']['arguments']['data']
                ['config']['imports']['__disableTmpl']['templateId'] = false;
            }
        }
        
        $meta = array_merge_recursive(
            $meta,
            [
                'general' => [
                    'children' => $fields,
                ],
            ]
        );
        return $meta;
    }
    
    /**
     * Template Images
     *
     * @return multitype:multitype:
     */
    public function getTemplateImages()
    {
        $images = [];
        foreach ($this->templates as $template) {
            if ($template->getImages()) {
                $images[$template->getId()] = explode(',', (string)$template->getImages());
            } else {
                $images[$template->getId()] = [];
            }
        }
        return $images;
    }
    
    /**
     * @inheritDoc
     */
    public function modifyData(array $data)
    {
        return $data;
    }
}
