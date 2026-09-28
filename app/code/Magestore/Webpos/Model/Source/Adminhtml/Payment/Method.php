<?php
declare(strict_types=1);

namespace Magestore\Webpos\Model\Source\Adminhtml\Payment;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Class Method
 *
 * Used to get payment method options source
 */
class Method implements OptionSourceInterface
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var array
     */
    protected $mappingPaymentConfigurationAndPaymentCode;

    /**
     * @var array
     */
    protected $methodWithoutConfig;

    /**
     * Method constructor
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param array $mappingPaymentConfigurationAndPaymentCode
     * @param array $methodWithoutConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        array $mappingPaymentConfigurationAndPaymentCode = [],
        array $methodWithoutConfig = []
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->mappingPaymentConfigurationAndPaymentCode = $mappingPaymentConfigurationAndPaymentCode;
        $this->methodWithoutConfig = $methodWithoutConfig;
    }

    /**
     * To option array label
     *
     * @return array
     */
    public function toOptionArray()
    {
        $result = [];
        $options = $this->getOptionHash();
        foreach ($options as $code => $title) {
            $result[] = [
                'value' => $code,
                'label' => $title
            ];
        }
        return $result;
    }

    /**
     * Get all options hash
     *
     * @return array
     */
    public function getOptionHash()
    {
        $options = [];
        $mapping = $this->mappingPaymentConfigurationAndPaymentCode;
        $paymentMethods = $this->scopeConfig->getValue('webpos/payment');
        foreach ($paymentMethods as $code => $paymentMethod) {
            if (isset($mapping[$code])) {
                $code = $mapping[$code];
            }
            if (is_array($paymentMethod) && isset($paymentMethod['title'])) {
                $options[$code] = $paymentMethod['title'];
            }
        }
        foreach ($this->methodWithoutConfig as $code => $title) {
            $options[$code] = __($title);
        }
        return $options;
    }
}
