<?php
/**
 *  Copyright © 2020 Magestore. All rights reserved.
 *  See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\InventoryMovementAdminUi\Ui\Component\Listing\Columns;

use Magento\Framework\Serialize\Serializer\Json as JsonSerialize;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magestore\InventoryMovementApi\Api\Data\InventoryMovementInterface;
use Magestore\InventoryMovementApi\Model\MetadataObjectTypes;

/**
 * Column note
 */
class Note extends Column
{
    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var JsonSerialize
     */
    protected $serializer;

    /**
     * @var MetadataObjectTypes
     */
    protected $metadataObjectTypes;

    /**
     * Note constructor.
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param JsonSerialize $serializer
     * @param MetadataObjectTypes $metadataObjectTypes
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        JsonSerialize $serializer,
        MetadataObjectTypes $metadataObjectTypes,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->urlBuilder = $urlBuilder;
        $this->serializer = $serializer;
        $this->metadataObjectTypes = $metadataObjectTypes;
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $name = $this->getData('name');
                if (isset($item[InventoryMovementInterface::METADATA])) {
                    $item[$name] = $this->getNoteFromMetadata($item[InventoryMovementInterface::METADATA]);
                }
            }
        }

        return $dataSource;
    }

    /**
     * Get Note From Metadata
     *
     * @param string $metadata
     * @return string
     */
    public function getNoteFromMetadata(string $metadata = "")
    {
        if (!$metadata) {
            return "";
        }

        $metadata = $this->serializer->unserialize($metadata);

        $note = isset($metadata[InventoryMovementInterface::ACTION_LABEL]) ?
            $metadata[InventoryMovementInterface::ACTION_LABEL]
            : "";

        $objectTypesData = $this->metadataObjectTypes->getData();
        if (isset($metadata[InventoryMovementInterface::OBJECT_TYPE])
            && isset($metadata[InventoryMovementInterface::OBJECT_ID])
            && isset($metadata[InventoryMovementInterface::OBJECT_INCREMENT_ID])
            && isset($objectTypesData[$metadata[InventoryMovementInterface::OBJECT_TYPE]])
        ) {
            $objectType = $metadata[InventoryMovementInterface::OBJECT_TYPE];
            $objectId = $metadata[InventoryMovementInterface::OBJECT_ID];
            $objectInfo = $objectTypesData[$objectType];

            $url = $this->urlBuilder->getUrl(
                $objectInfo[MetadataObjectTypes::VIEW_URL_PATH],
                [$objectInfo[MetadataObjectTypes::URL_ENTITY_PARAM_NAME] => $objectId]
            );

            $note .= ': '
                . '<a href="' . $url . '">'
                . '#' . $metadata[InventoryMovementInterface::OBJECT_INCREMENT_ID]
                . '</a>';
        }
        return $note;
    }
}
