<?php
declare(strict_types=1);

namespace ArtM\ProductSlider\Controller\Adminhtml\Slider;

use ArtM\ProductSlider\Api\SliderRepositoryInterface;
use ArtM\ProductSlider\Model\SliderFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class Save extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_ProductSlider::slider_save';
    private const FALLBACK_PAYLOAD_KEY = '__artm_slider_fallback';

    public function __construct(
        Context $context,
        private readonly SliderFactory $sliderFactory,
        private readonly SliderRepositoryInterface $sliderRepository,
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

        $sliderData = (array)($data['slider'] ?? $data);
        $sliderId = isset($sliderData['slider_id']) ? (int)$sliderData['slider_id'] : 0;
        if ($sliderId <= 0) {
            unset($sliderData['slider_id']);
            $sliderId = 0;
        }
        try {
            $slider = $sliderId ? $this->sliderRepository->getById($sliderId) : $this->sliderFactory->create();
            $existingData = (array)$slider->getData();

            $selectedGroupIds = [];
            if (array_key_exists('group_ids', $sliderData)) {
                $rawGroupIds = $sliderData['group_ids'];
                if (is_array($rawGroupIds)) {
                    $selectedGroupIds = array_values(array_unique(array_filter(array_map('intval', $rawGroupIds), static fn(int $v): bool => $v > 0)));
                } else {
                    $selectedGroupIds = array_values(array_unique(array_filter(array_map('intval', explode(',', (string)$rawGroupIds)), static fn(int $v): bool => $v > 0)));
                }
            } elseif (array_key_exists('group_id', $sliderData) && $sliderData['group_id'] !== '') {
                $legacyGroupId = (int)$sliderData['group_id'];
                if ($legacyGroupId > 0) {
                    $selectedGroupIds = [$legacyGroupId];
                }
            }

            $sliderData['group_id'] = $selectedGroupIds !== [] ? $selectedGroupIds[0] : null;
            $sliderData['group_ids'] = $selectedGroupIds;

            if (array_key_exists('slides_to_show', $sliderData)) {
                $slidesToShow = $sliderData['slides_to_show'];
                if (is_array($slidesToShow)) {
                    $slidesToShow = array_filter(array_map('strval', $slidesToShow), static fn(string $value): bool => $value !== '');
                    $sliderData['slides_to_show'] = implode(',', $slidesToShow);
                } else {
                    $sliderData['slides_to_show'] = (string)$slidesToShow;
                }

                if ($sliderData['slides_to_show'] === '') {
                    $sliderData['slides_to_show'] = '4';
                }
            } elseif (!$sliderId && empty($existingData['slides_to_show'])) {
                $sliderData['slides_to_show'] = '4';
            }

            if (
                (array_key_exists('identifier', $sliderData) && trim((string)$sliderData['identifier']) === '')
                || (!array_key_exists('identifier', $sliderData) && !empty($existingData['identifier']) === false)
            ) {
                $titleForIdentifier = (string)($sliderData['title'] ?? $existingData['title'] ?? '');
                if ($titleForIdentifier !== '') {
                    $identifier = strtolower($titleForIdentifier);
                    $identifier = preg_replace('/[^a-z0-9]+/', '-', $identifier) ?: '';
                    $sliderData['identifier'] = trim((string)$identifier, '-');
                }
            }

            $csvKeys = [
            'store_view_ids',
            'customer_group_ids',
            'category_ids',
            'source_product_ids',
            'mixed_category_ids',
            'mixed_product_ids',
            'mixed_excluded_product_ids',
            'card_components',
            'custom_attributes',
            ];
            foreach ($csvKeys as $key) {
                if (!array_key_exists($key, $sliderData)) {
                    continue;
                }

                $value = $sliderData[$key];
                if (is_array($value)) {
                    $value = array_filter(array_map('strval', $value), static fn(string $v): bool => $v !== '');
                    $sliderData[$key] = implode(',', $value);
                } else {
                    $sliderData[$key] = (string)$value;
                }
            }

            foreach (['source_product_ids', 'mixed_product_ids', 'mixed_excluded_product_ids', 'exclude_product_ids', 'category_ids', 'mixed_category_ids'] as $key) {
                if (!array_key_exists($key, $sliderData)) {
                    continue;
                }
                $ids = array_values(array_unique(array_filter(array_map('intval', explode(',', (string)$sliderData[$key])), static fn(int $v): bool => $v > 0)));
                $sliderData[$key] = implode(',', $ids);
            }

            if (array_key_exists('manual_sort_product_ids', $sliderData)) {
                $manualOrder = array_values(array_unique(array_filter(array_map('intval', explode(',', (string)$sliderData['manual_sort_product_ids'])), static fn(int $v): bool => $v > 0)));
                $allowed = array_values(array_unique(array_merge(
                    array_filter(array_map('intval', explode(',', (string)($sliderData['source_product_ids'] ?? ''))), static fn(int $v): bool => $v > 0),
                    array_filter(array_map('intval', explode(',', (string)($sliderData['mixed_product_ids'] ?? ''))), static fn(int $v): bool => $v > 0)
                )));
                $allowedMap = array_flip($allowed);
                $manualOrder = array_values(array_filter($manualOrder, static fn(int $id): bool => isset($allowedMap[$id])));
                $sliderData['manual_sort_product_ids'] = implode(',', $manualOrder);
            }

            $intKeys = [
            'status',
            'sort_order',
            'allow_multiple_categories',
            'include_child_categories',
            'product_limit',
            'max_products_desktop',
            'max_products_tablet',
            'max_products_mobile',
            'desktop_items',
            'tablet_items',
            'mobile_items',
            'space_between',
            'show_arrows',
            'show_dots',
            'infinite_loop',
            'autoplay',
            'autoplay_delay',
            'autoplay_speed',
            'swipe',
            'mouse_drag',
            'touch_support',
            'lazy_load',
            'animation_speed',
            'grid_desktop_columns',
            'grid_tablet_columns',
            'grid_mobile_columns',
            'hover_image_enabled',
            'show_name',
            'show_price',
            'show_add_to_cart',
            ];
            foreach ($intKeys as $key) {
                if (!array_key_exists($key, $sliderData)) {
                    continue;
                }
                $sliderData[$key] = (int)$sliderData[$key];
            }

            $tableColumns = array_keys($slider->getResource()->getConnection()->describeTable($slider->getResource()->getMainTable()));
            $fallbackPayload = $this->extractFallbackPayload((string)($existingData['source_value'] ?? ''));
            $missingColumns = [];
            foreach (array_keys($sliderData) as $field) {
                if ($field === 'slider_id') {
                    continue;
                }
                if (!in_array($field, $tableColumns, true)) {
                    $missingColumns[] = $field;
                    $fallbackPayload[$field] = $sliderData[$field];
                    unset($sliderData[$field]);
                    continue;
                }

                unset($fallbackPayload[$field]);
            }

            if ($missingColumns !== []) {
                $sliderData['source_value'] = $this->encodeFallbackPayload($fallbackPayload);
                $this->messageManager->addNoticeMessage(
                    __('Saved using temporary fallback storage for missing columns: %1. Run setup:upgrade to persist in dedicated DB columns.', implode(', ', $missingColumns))
                );
            } elseif ($fallbackPayload !== []) {
                $sliderData['source_value'] = $this->encodeFallbackPayload($fallbackPayload);
            }

            $slider->setData(array_merge($existingData, $sliderData));
            $this->sliderRepository->save($slider);

            if (!$sliderId && (int)$slider->getId() <= 0) {
                throw new LocalizedException(__('Unable to create slider record. Please check database table auto-increment and permissions.'));
            }

            $this->messageManager->addSuccessMessage(__('The slider has been saved.'));
            $this->dataPersistor->clear('artm_productslider_slider');

            if ($this->getRequest()->getParam('back')) {
                return $resultRedirect->setPath('*/*/edit', ['slider_id' => $slider->getId()]);
            }

            return $resultRedirect->setPath('*/*/index');
        } catch (LocalizedException $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
        } catch (\Throwable $exception) {
            $this->logger->critical($exception);
            $this->messageManager->addErrorMessage(__('Unable to save the slider. %1', $exception->getMessage()));
        }

        $this->dataPersistor->set('artm_productslider_slider', $sliderData);

        if ($sliderId) {
            return $resultRedirect->setPath('*/*/edit', ['slider_id' => $sliderId]);
        }

        return $resultRedirect->setPath('*/*/new');
    }

    private function extractFallbackPayload(string $sourceValue): array
    {
        if ($sourceValue === '') {
            return [];
        }

        $decoded = json_decode($sourceValue, true);
        if (!is_array($decoded)) {
            return [];
        }

        $payload = $decoded[self::FALLBACK_PAYLOAD_KEY] ?? null;
        return is_array($payload) ? $payload : [];
    }

    private function encodeFallbackPayload(array $payload): string
    {
        return (string)json_encode([self::FALLBACK_PAYLOAD_KEY => $payload]);
    }
}
