<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magestore\Webpos\Plugin\Order\Grid;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\RequestInterface;
use Magento\Ui\Model\Export\MetadataProvider;
use Magestore\Webpos\Model\Source\Adminhtml\Location;
use Magestore\Webpos\Model\Source\Adminhtml\Payment\Method;
use Magestore\Webpos\Model\Source\Adminhtml\Staff;
use Magento\Sales\Model\ResourceModel\Order\Grid\Collection as CollectionCore;

/**
 * Class Collection
 *
 * Order Grid Collection Plugin
 */
class Collection
{
    /**
     * @var Location
     */
    protected $locationSource;

    protected $locationSourceOptions;

    /**
     * @var Staff
     */
    protected $staffSource;

    protected $staffOptions;

    /**
     * @var Method
     */
    protected $paymentMethodSource;

    /**
     * Collection construct
     *
     * @param Location $locationSource
     * @param Staff $staffSource
     * @param Method|null $paymentMethodSource
     */
    public function __construct(
        Location $locationSource,
        Staff $staffSource,
        ?Method $paymentMethodSource = null
    ) {
        $this->locationSource = $locationSource;
        $this->staffSource = $staffSource;
        $this->paymentMethodSource = $paymentMethodSource  ?: ObjectManager::getInstance()->get(Method::class);
    }

    /**
     * After Get Data
     *
     * @param CollectionCore $subject
     * @param array $result
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetData(
        CollectionCore $subject,
        $result
    ) {
        $objectManager = ObjectManager::getInstance();
        $requestInterface = $objectManager->get(RequestInterface::class);
        $metadataProvider = $objectManager->get(MetadataProvider::class);
        $result = $this->renderLabelExportCsv($requestInterface, $result);
        if (!method_exists($metadataProvider, 'getColumnOptions')) {
            if (($requestInterface->getActionName() == 'gridToCsv')
                || ($requestInterface->getActionName() == 'gridToXml')
            ) {
                $options = $this->getLocationSourceOptions();
                $optionsFulfill = ['0'=> 'No','1'=>'Yes'];
                $optionsStaff = $this->getStaffOptions();
                foreach ($result as &$item) {
                    if ($item['pos_location_id']) {
                        $item['pos_location_id'] = $options[$item['pos_location_id']];
                    }
                    if (isset($item['pos_fulfill_online'])) {
                        $item['pos_fulfill_online'] = $optionsFulfill[$item['pos_fulfill_online']];
                    }
                    if (isset($item['pos_staff_id']) && isset($optionsStaff[$item['pos_staff_id']])) {
                        $item['pos_staff_id'] = $optionsStaff[$item['pos_staff_id']];
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Get Location Source Options
     *
     * @return array
     */
    public function getLocationSourceOptions()
    {
        if (!$this->locationSourceOptions) {
            $this->locationSourceOptions = $this->locationSource->getOptionArray();
        }
        return $this->locationSourceOptions;
    }

    /**
     * Get Staff Options
     *
     * @return array
     */
    public function getStaffOptions()
    {
        if (!$this->staffOptions) {
            $this->staffOptions = $this->staffSource->getOptionArray();
        }
        return $this->staffOptions;
    }

    /**
     * Render multi value when export csv
     *
     * @param RequestInterface $requestInterface
     * @param array $result
     */
    public function renderLabelExportCsv($requestInterface, $result)
    {
        if (($requestInterface->getActionName() == 'gridToCsv')
            || ($requestInterface->getActionName() == 'gridToXml')
        ) {
            $paymentOptions = $this->paymentMethodSource->getOptionHash();
            foreach ($result as &$item) {
                if (isset($item['pos_payment_method']) && $item['pos_payment_method']) {
                    $posPaymentMethod = $item['pos_payment_method'];
                    $posPaymentMethodArray = explode(',', (string)$posPaymentMethod);
                    $posPaymentMethodLabelArray = [];
                    foreach ($posPaymentMethodArray as $posPaymentMethodValue) {
                        if (isset($paymentOptions[$posPaymentMethodValue])) {
                            $posPaymentMethodLabelArray[] = $paymentOptions[$posPaymentMethodValue];
                        }
                    }
                    $item['pos_payment_method'] = implode(',', $posPaymentMethodLabelArray);
                }
            }
        }
        return $result;
    }
}
