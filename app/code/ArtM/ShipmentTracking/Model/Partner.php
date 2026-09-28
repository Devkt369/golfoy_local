<?php

declare(strict_types=1);

namespace ArtM\ShipmentTracking\Model;

use ArtM\ShipmentTracking\Model\ResourceModel\Partner as ResourceModel;
use Magento\Framework\Model\AbstractModel;

/**
 * @method int getPartnerId()
 * @method string getPartnerName()
 * @method int getIsActive()
 * @method string getApiUrl()
 * @method string getUsername()
 * @method string getAuthType()
 * @method string getToken()
 * @method string getLastSyncAt()
 * @method self setPartnerName(string $value)
 * @method self setIsActive(int $value)
 * @method self setApiUrl(string $value)
 * @method self setUsername(string $value)
 * @method self setAuthType(string $value)
 * @method self setToken(string $value)
 * @method self setLastSyncAt(string $value)
 */
class Partner extends AbstractModel
{
    protected function _construct(): void
    {
        $this->_init(ResourceModel::class);
    }
}
