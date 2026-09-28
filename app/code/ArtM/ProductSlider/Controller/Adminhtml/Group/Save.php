<?php
declare(strict_types=1);

namespace ArtM\ProductSlider\Controller\Adminhtml\Group;

use ArtM\ProductSlider\Api\SliderGroupRepositoryInterface;
use ArtM\ProductSlider\Model\SliderGroupFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class Save extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_ProductSlider::group_save';

    public function __construct(
        Context $context,
        private readonly SliderGroupFactory $sliderGroupFactory,
        private readonly SliderGroupRepositoryInterface $sliderGroupRepository,
        private readonly DataPersistorInterface $dataPersistor,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct($context);
    }

    public function execute(): Redirect
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $data = (array)$this->getRequest()->getPostValue();

        if ($data === []) {
            return $resultRedirect->setPath('*/*/index');
        }

        $groupData = (array)($data['group'] ?? $data);
        $groupId = isset($groupData['group_id']) ? (int)$groupData['group_id'] : 0;
        if ($groupId <= 0) {
            unset($groupData['group_id']);
            $groupId = 0;
        }
        $groupData['sort_order'] = isset($groupData['sort_order']) ? (int)$groupData['sort_order'] : 0;

        $settings = [
            'store_view_ids' => isset($groupData['store_view_ids']) && is_array($groupData['store_view_ids'])
                ? array_values(array_filter(array_map('strval', $groupData['store_view_ids']), static fn(string $v): bool => $v !== ''))
                : [],
            'customer_group_ids' => isset($groupData['customer_group_ids']) && is_array($groupData['customer_group_ids'])
                ? array_values(array_filter(array_map('strval', $groupData['customer_group_ids']), static fn(string $v): bool => $v !== ''))
                : [],
            'heading' => (string)($groupData['heading'] ?? ''),
            'sub_heading' => (string)($groupData['sub_heading'] ?? ''),
            'show_desktop' => isset($groupData['show_desktop']) ? ((int)$groupData['show_desktop'] === 1 ? 1 : 0) : 1,
            'show_tablet' => isset($groupData['show_tablet']) ? ((int)$groupData['show_tablet'] === 1 ? 1 : 0) : 1,
            'show_mobile' => isset($groupData['show_mobile']) ? ((int)$groupData['show_mobile'] === 1 ? 1 : 0) : 1,
        ];

        $tabs = [];
        if (isset($groupData['tabs']) && is_array($groupData['tabs'])) {
            foreach ($groupData['tabs'] as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $sliderId = isset($row['slider_id']) ? (int)$row['slider_id'] : 0;
                $tabName = trim((string)($row['tab_name'] ?? ''));
                if ($sliderId <= 0 && $tabName === '') {
                    continue;
                }

                // Heal malformed older payloads where a row contains only tab name
                // and the preceding row contains only slider_id.
                if ($sliderId <= 0 && $tabName !== '') {
                    $lastIndex = count($tabs) - 1;
                    if ($lastIndex >= 0 && (int)($tabs[$lastIndex]['slider_id'] ?? 0) > 0 && trim((string)($tabs[$lastIndex]['tab_name'] ?? '')) === '') {
                        $tabs[$lastIndex]['tab_name'] = $tabName;
                        continue;
                    }

                    // Skip slider-less rows; each tab must point to a slider.
                    continue;
                }

                $viewAllOverride = (string)($row['view_all_override'] ?? 'use_slider_button');
                if (!in_array($viewAllOverride, ['use_slider_button', 'manual_url', 'hide'], true)) {
                    $viewAllOverride = 'use_slider_button';
                }

                $tabs[] = [
                    'slider_id' => $sliderId,
                    'tab_name' => $tabName,
                    'view_all_override' => $viewAllOverride,
                    'manual_url' => (string)($row['manual_url'] ?? ''),
                    'sort_order' => isset($row['sort_order']) ? (int)$row['sort_order'] : 0,
                    'is_default' => isset($row['is_default']) ? (int)$row['is_default'] : 0,
                ];
            }
        }

        usort($tabs, static fn(array $a, array $b): int => ($a['sort_order'] <=> $b['sort_order']));
        $hasDefault = false;
        foreach ($tabs as $index => $tab) {
            if ((int)$tab['is_default'] === 1 && !$hasDefault) {
                $hasDefault = true;
                $tabs[$index]['is_default'] = 1;
            } else {
                $tabs[$index]['is_default'] = 0;
            }
        }
        if (!$hasDefault && $tabs !== []) {
            $tabs[0]['is_default'] = 1;
        }

        $groupData['tabs_json'] = (string)json_encode([
            'settings' => $settings,
            'tabs' => $tabs,
        ]);

        unset(
            $groupData['store_view_ids'],
            $groupData['customer_group_ids'],
            $groupData['heading'],
            $groupData['sub_heading'],
            $groupData['show_desktop'],
            $groupData['show_tablet'],
            $groupData['show_mobile'],
            $groupData['tabs']
        );

        if (!empty($groupData['tabs_json'])) {
            json_decode((string)$groupData['tabs_json'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->messageManager->addErrorMessage(__('Tabs JSON is invalid.'));
                $this->dataPersistor->set('artm_productslider_group', $groupData);

                if ($groupId) {
                    return $resultRedirect->setPath('*/*/edit', ['group_id' => $groupId]);
                }

                return $resultRedirect->setPath('*/*/new');
            }
        }

        try {
            $group = $groupId ? $this->sliderGroupRepository->getById($groupId) : $this->sliderGroupFactory->create();
            $group->setData(array_merge($group->getData(), $groupData));
            $this->sliderGroupRepository->save($group);

            if (!$groupId && (int)$group->getId() <= 0) {
                throw new LocalizedException(__('Unable to create slider group record. Please check database table auto-increment and permissions.'));
            }

            $this->messageManager->addSuccessMessage(__('The slider group has been saved.'));
            $this->dataPersistor->clear('artm_productslider_group');

            if ($this->getRequest()->getParam('back')) {
                return $resultRedirect->setPath('*/*/edit', ['group_id' => $group->getId()]);
            }

            return $resultRedirect->setPath('*/*/index');
        } catch (LocalizedException $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
        } catch (\Throwable $exception) {
            $this->logger->critical($exception);
            $this->messageManager->addErrorMessage(__('Unable to save the slider group. %1', $exception->getMessage()));
        }

        $this->dataPersistor->set('artm_productslider_group', $groupData);

        if ($groupId) {
            return $resultRedirect->setPath('*/*/edit', ['group_id' => $groupId]);
        }

        return $resultRedirect->setPath('*/*/new');
    }
}
