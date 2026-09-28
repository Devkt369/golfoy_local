<?php

/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectAdminUi\Controller\Adminhtml\SpecialDay;

use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Backend\App\Action\Context;
use Magestore\ClickAndCollectApi\Api\Data\SpecialDayInterface;
use Magestore\ClickAndCollectApi\Api\SpecialDayRepositoryInterface;
use Magestore\ClickAndCollectApi\Api\Data\SpecialDayInterfaceFactory;

/**
 * Class Save
 *
 * Use for saving special day
 */
class Save extends \Magento\Backend\App\Action implements HttpPostActionInterface
{
    /**
     * Authorization
     */
    const ADMIN_RESOURCE = 'Magestore_ClickAndCollectAdminUi::specialday';

    /**
     * @var SpecialDayInterfaceFactory
     */
    protected $specialDayInterfaceFactory;

    /**
     * @var SpecialDayRepositoryInterface
     */
    protected $specialDayRepository;

    /**
     * Save constructor.
     *
     * @param Context $context
     * @param SpecialDayInterfaceFactory $specialDayInterfaceFactory
     * @param SpecialDayRepositoryInterface $specialDayRepository
     */
    public function __construct(
        Context $context,
        SpecialDayInterfaceFactory $specialDayInterfaceFactory,
        SpecialDayRepositoryInterface $specialDayRepository
    ) {
        parent::__construct($context);
        $this->specialDayInterfaceFactory = $specialDayInterfaceFactory;
        $this->specialDayRepository = $specialDayRepository;
    }

    /**
     * Save action.
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $postValue = $this->getRequest()->getPostValue();
        if (!$postValue) {
            return $resultRedirect->setPath('*/*/');
        }

        $dataSave = $this->prepareSave($postValue);

        $model = $this->specialDayInterfaceFactory->create();
        $model->setData($dataSave);

        try {
            $this->specialDayRepository->save($model);
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__("The special day could not be saved."));
            $this->_getSession()->setFormData($postValue);
            return $resultRedirect->setPath('*/*/new');
        }

        $this->messageManager->addSuccessMessage(__("The special day has been saved successfully"));
        return $resultRedirect->setPath('*/*/');
    }

    /**
     * Prepare data to save
     *
     * @param array $postData
     * @return array
     */
    public function prepareSave(array $postData)
    {
        $dataInformation = $postData['general_information'];
        $key = SpecialDayInterface::TIME_OPEN;
        if (isset($dataInformation[$key . '_group'])) {
            $dataInformation[$key] = $dataInformation[$key . '_group'][$key . '_hour'] . ':'
                . $dataInformation[$key . '_group'][$key . '_min'];
            unset($dataInformation[$key . '_group']);
        }
        $key = SpecialDayInterface::TIME_CLOSE;
        if (isset($dataInformation[$key . '_group'])) {
            $dataInformation[$key] = $dataInformation[$key . '_group'][$key . '_hour'] . ':'
                . $dataInformation[$key . '_group'][$key . '_min'];
            unset($dataInformation[$key . '_group']);
        }
        return $dataInformation;
    }
}
