<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollect\Model\DataProvider;

use Magestore\ClickAndCollectApi\Api\Data\SpecialDayInterface;
use Magestore\ClickAndCollectApi\Api\DataProvider\GetSpecialDayByIdInterface;
use Magestore\ClickAndCollectApi\Api\SpecialDayRepositoryInterface;

/**
 * Class GetSpecialDayById
 *
 * Use for save special day when query by id
 */
class GetSpecialDayById implements GetSpecialDayByIdInterface
{
    /**
     * @var SpecialDayInterface[]
     */
    protected $specialDayLoaded = [];

    /**
     * @var SpecialDayRepositoryInterface
     */
    protected $specialDayRepository;

    /**
     * GetSpecialDayById constructor.
     *
     * @param SpecialDayRepositoryInterface $specialDayRepository
     */
    public function __construct(
        SpecialDayRepositoryInterface $specialDayRepository
    ) {
        $this->specialDayRepository = $specialDayRepository;
    }

    /**
     * @inheritDoc
     */
    public function execute(int $specialDayId): SpecialDayInterface
    {
        if (!isset($this->specialDayLoaded[$specialDayId])) {
            $this->specialDayLoaded[$specialDayId] = $this->specialDayRepository->get($specialDayId);
        }
        return $this->specialDayLoaded[$specialDayId];
    }
}
