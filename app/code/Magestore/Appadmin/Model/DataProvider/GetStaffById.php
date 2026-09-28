<?php
/**
 * Copyright © 2018 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types = 1);

namespace Magestore\Appadmin\Model\DataProvider;

use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magestore\Appadmin\Api\Data\Staff\StaffInterface;
use Magestore\Appadmin\Api\DataProvider\GetStaffByIdInterface;
use Magestore\Appadmin\Api\Staff\StaffRepositoryInterface;

/**
 * Class GetCurrentStaffById
 *
 * Data Provider to get current staff by id
 */
class GetStaffById extends DataObject implements GetStaffByIdInterface
{
    /**
     * @var StaffRepositoryInterface
     */
    protected $staffRepository;

    /**
     * GetCurrentStaffById constructor.
     *
     * @param StaffRepositoryInterface $staffRepository
     * @param array $data
     */
    public function __construct(
        StaffRepositoryInterface $staffRepository,
        $data = []
    ) {
        $this->staffRepository = $staffRepository;
        parent::__construct($data);
    }

    /**
     * Get staff by id
     *
     * @param int $id
     * @return StaffInterface|null
     * @throws LocalizedException
     */
    public function execute(int $id)
    {
        $staffData = $this->_getData(self::STAFF_DATA);
        if (!is_array($staffData)) {
            $staffData = [];
        }
        if (!isset($staffData[$id])) {
            $staffData[$id] = $this->staffRepository->getById($id);
            $this->setData(self::STAFF_DATA, $staffData);
        }
        return $staffData[$id];
    }
}
