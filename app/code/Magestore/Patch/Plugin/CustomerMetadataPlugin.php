<?php

namespace Magestore\Patch\Plugin;

use Magestore\Webpos\Model\Customer\CustomerMetadata;

/**
 * Class CustomerMetadataPlugin
 */
class CustomerMetadataPlugin
{
    /**
     * After get attributes
     *
     * @param CustomerMetadata $subject
     * @param array $result
     * @param string $formCode
     * @return array
     * @see CustomerMetadata::getAttributes()
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetAttributes($subject, $result, $formCode)
    {
        if (isset($result['magesms_customer_marketing'])) {
            unset($result['magesms_customer_marketing']);
        }
        return $result;
    }
}
