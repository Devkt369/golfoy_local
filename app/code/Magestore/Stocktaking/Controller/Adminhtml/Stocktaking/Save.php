<?php

/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\Stocktaking\Controller\Adminhtml\Stocktaking;

use Exception;
use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\User\Model\User;
use Magestore\Stocktaking\Api\Data\StocktakingInterface;
use Magento\Backend\Model\Auth\Session as AuthSession;
use Magento\User\Model\UserFactory;
use Magento\User\Model\ResourceModel\User as UserResource;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magestore\Stocktaking\Api\Data\StocktakingInterfaceFactory;
use Magestore\Stocktaking\Model\ResourceModel\Stocktaking as StocktakingResource;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Stdlib\DateTime\Filter\Date as DateFilter;
use Zend_Filter_Input;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

/**
 * Class Save
 *
 * Use for saving stocktaking
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Save extends Action implements HttpPostActionInterface
{
    /**
     * Authorization
     */
    const ADMIN_RESOURCE = 'Magestore_Stocktaking::create_stocktaking';
    
    /**
     * @var AuthSession
     */
    protected $authSession;

    /**
     * @var UserFactory
     */
    protected $userFactory;

    /**
     * @var SourceRepositoryInterface
     */
    protected $sourceRepository;

    /**
     * @var StocktakingInterfaceFactory
     */
    protected $stocktakingInterfaceFactory;

    /**
     * @var StocktakingResource
     */
    protected $stocktakingResource;

    /**
     * @var UserResource
     */
    protected $userResource;

    /**
     * @var DateFilter
     */
    protected $dateFilter;

    /**
     * @var TimezoneInterface
     */
    protected $localeDate;

    /**
     * Save constructor.
     *
     * @param Context $context
     * @param AuthSession $authSession
     * @param UserFactory $userFactory
     * @param UserResource $userResource
     * @param SourceRepositoryInterface $sourceRepository
     * @param StocktakingInterfaceFactory $stocktakingInterfaceFactory
     * @param StocktakingResource $stocktakingResource
     * @param DateFilter|null $dateFilter
     * @param TimezoneInterface|null $localeDate
     */
    public function __construct(
        Context $context,
        AuthSession $authSession,
        UserFactory $userFactory,
        UserResource $userResource,
        SourceRepositoryInterface $sourceRepository,
        StocktakingInterfaceFactory $stocktakingInterfaceFactory,
        StocktakingResource $stocktakingResource,
        DateFilter $dateFilter = null,
        TimezoneInterface $localeDate = null
    ) {
        parent::__construct($context);
        $this->authSession = $authSession;
        $this->userFactory = $userFactory;
        $this->userResource = $userResource;
        $this->sourceRepository = $sourceRepository;
        $this->stocktakingInterfaceFactory = $stocktakingInterfaceFactory;
        $this->stocktakingResource = $stocktakingResource;
        $this->dateFilter = $dateFilter ?: ObjectManager::getInstance()
            ->get(DateFilter::class);
        $this->localeDate = $localeDate ?: ObjectManager::getInstance()
            ->get(TimezoneInterface::class);
    }

    /**
     * Save action.
     *
     * @return ResultInterface
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        if (!$this->getRequest()->getPostValue()) {
            return $resultRedirect->setPath('*/*/');
        }
        
        $data = $this->prepareStocktakingData();

        /** @var StocktakingInterface $source */
        $model = $this->stocktakingInterfaceFactory->create();
        $model->setData($data);
        try {
            $this->stocktakingResource->save($model);
            if (!$model->getCode()) {
                $id = $model->getId();
                $stocktakingCode = 'ST' . str_pad((string) $id, 8, '0', STR_PAD_LEFT);
                $model->setCode($stocktakingCode);
                $this->stocktakingResource->save($model);
            }
        } catch (Exception $e) {
            $this->messageManager->addErrorMessage(__("The stock-taking could not be saved."));
            $this->_getSession()->setFormData($data);
            return $resultRedirect->setPath('*/*/new');
        }

        $this->messageManager->addSuccessMessage(__("The stock-taking has been saved successfully"));

        if ($this->getRequest()->getParam('back') == 'edit') {
            return $resultRedirect->setPath('*/*/edit', ['id' => $model->getId()]);
        }
        return $resultRedirect->setPath('*/*/edit', ['id' => $model->getId()]);
    }

    /**
     * Get Stocktaking Data
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function prepareStocktakingData(): array
    {
        $data = $this->getRequest()->getPostValue();
        // Apply solution in purchase order module
        // Not filter input when date has format Y-m-d (Default value without date picker is Y-m-d different datepicker)
        if ($this->localeDate->date()->format('Y-m-d') !== $data[StocktakingInterface::CREATED_AT]) {
            $inputFilter = new Zend_Filter_Input(
                [StocktakingInterface::CREATED_AT => $this->dateFilter],
                [],
                $data
            );
            $data = $inputFilter->getUnescaped();
        }

        if (!isset($data[StocktakingInterface::ID])) {
            $data[StocktakingInterface::CODE] = '';
            $data[StocktakingInterface::STATUS] =
                $data[StocktakingInterface::STOCKTAKING_TYPE] == StocktakingInterface::STOCKTAKING_TYPE_FULL ?
                    StocktakingInterface::STATUS_COUNTING
                    : StocktakingInterface::STATUS_PREPARING;
            /** @var User $curUser */
            $curUser = $this->authSession->getUser();
            $data[StocktakingInterface::CREATED_BY_ID] = $curUser->getId();
            $data[StocktakingInterface::CREATED_BY_FIRST_NAME] = $curUser->getFirstName();
            $data[StocktakingInterface::CREATED_BY_LAST_NAME] = $curUser->getLastName();
            
            if (isset($data[StocktakingInterface::ASSIGN_USER_ID])) {
                /** @var User $assignUser */
                $assignUser = $this->userFactory->create();
                $this->userResource->load($assignUser, $data[StocktakingInterface::ASSIGN_USER_ID]);
                if ($assignUser->getId()) {
                    $data[StocktakingInterface::ASSIGN_USER_FIRST_NAME] = $assignUser->getFirstName();
                    $data[StocktakingInterface::ASSIGN_USER_LAST_NAME] = $assignUser->getLastName();
                }
            }

            if (isset($data[StocktakingInterface::SOURCE_CODE])) {
                /** @var SourceInterface $source */
                $source = $this->sourceRepository->get($data[StocktakingInterface::SOURCE_CODE]);
                if ($source && $source->getSourceCode()) {
                    $data[StocktakingInterface::SOURCE_NAME] = $source->getName();
                }
            }
        }
        return $data;
    }
}
