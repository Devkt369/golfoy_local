<?php
/**
 *  Copyright © 2020 Magestore. All rights reserved.
 *  See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\InventoryMovementAdminUi\Ui\Component\Listing\Columns;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magestore\InventoryMovementApi\Model\UserTypes;
use Magestore\InventoryMovementApi\Api\Data\InventoryMovementInterface;

/**
 * Column user
 */
class User extends Column
{
    /**
     * @var UserTypes
     */
    protected $userTypes;

    /**
     * User constructor.
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UserTypes $userTypes
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UserTypes $userTypes,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->userTypes = $userTypes;
    }

    /**
     * Modify data source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $fieldName = $this->getData('name');
            $userTypes = $this->userTypes->getData();
            foreach ($dataSource['data']['items'] as & $item) {
                $userType = $item[InventoryMovementInterface::USER_TYPE];
                if ($item[$fieldName]
                    && $userType
                    && isset($userTypes[$userType])
                ) {
                    $item[$fieldName] = __($userTypes[$userType]) . ": " . $item[$fieldName];
                }
            }
        }
        return $dataSource;
    }
}
