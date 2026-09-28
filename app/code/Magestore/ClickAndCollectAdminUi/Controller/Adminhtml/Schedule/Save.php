<?php

/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectAdminUi\Controller\Adminhtml\Schedule;

use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Backend\App\Action\Context;
use Magestore\ClickAndCollectApi\Api\ScheduleRepositoryInterface;
use Magestore\ClickAndCollectApi\Api\Data\ScheduleInterfaceFactory;

/**
 * Class Save
 *
 * Use for saving stocktaking
 */
class Save extends \Magento\Backend\App\Action implements HttpPostActionInterface
{
    /**
     * Authorization
     */
    const ADMIN_RESOURCE = 'Magestore_ClickAndCollectAdminUi::schedule';

    /**
     * @var ScheduleInterfaceFactory
     */
    protected $scheduleInterfaceFactory;

    /**
     * @var ScheduleRepositoryInterface
     */
    protected $scheduleRepository;

    /**
     * Save constructor.
     *
     * @param Context $context
     * @param ScheduleInterfaceFactory $scheduleInterfaceFactory
     * @param ScheduleRepositoryInterface $scheduleRepository
     */
    public function __construct(
        Context $context,
        ScheduleInterfaceFactory $scheduleInterfaceFactory,
        ScheduleRepositoryInterface $scheduleRepository
    ) {
        parent::__construct($context);
        $this->scheduleInterfaceFactory = $scheduleInterfaceFactory;
        $this->scheduleRepository = $scheduleRepository;
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

        $model = $this->scheduleInterfaceFactory->create();
        $model->setData($dataSave);

        try {
            $this->scheduleRepository->save($model);
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__("The schedule could not be saved."));
            $this->_getSession()->setFormData($postValue);
            return $resultRedirect->setPath('*/*/new');
        }

        $this->messageManager->addSuccessMessage(__("The schedule has been saved successfully"));
        $redirectBack = $this->getRequest()->getParam('back', false);
        if ($redirectBack == 'edit') {
            return $resultRedirect->setPath('*/*/edit', ['schedule_id' => $model->getScheduleId()]);
        } elseif ($redirectBack == 'new') {
            return $resultRedirect->setPath('*/*/new');
        } else {
            return $resultRedirect->setPath('*/*/index', ['schedule_id' => $model->getScheduleId()]);
        }
    }

    /**
     * Prepare data to save
     *
     * @param array $postData
     * @return array
     */
    public function prepareSave(array $postData)
    {
        $dataInformation = $postData['schedule_information_section'];
        $weekday = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        foreach ($weekday as $day) {
            if (isset($dataInformation[$day . '_open_group'])) {
                $dataInformation[$day. '_open'] = $dataInformation[$day . '_open_group'][$day . '_open_hour'] . ':'
                    . $dataInformation[$day . '_open_group'][$day . '_open_min'];
                unset($dataInformation[$day . '_open_group']);
            }
            if (isset($dataInformation[$day . '_close_group'])) {
                $dataInformation[$day. '_close'] = $dataInformation[$day . '_close_group'][$day . '_close_hour'] . ':'
                    . $dataInformation[$day . '_close_group'][$day . '_close_min'];
                unset($dataInformation[$day . '_close_group']);
            }
        }
        return $dataInformation;
    }
}
