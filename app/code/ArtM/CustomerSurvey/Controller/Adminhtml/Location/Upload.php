<?php

namespace ArtM\CustomerSurvey\Controller\Adminhtml\Location;

use ArtM\CustomerSurvey\Model\ImageUploader;
use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;

class Upload extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_CustomerSurvey::location_save';

    /**
     * @var ImageUploader
     */
    private $imageUploader;

    public function __construct(
        Action\Context $context,
        ImageUploader $imageUploader
    ) {
        parent::__construct($context);
        $this->imageUploader = $imageUploader;
    }

    public function execute()
    {
        try {
            $result = $this->imageUploader->saveFileToTmpDir('screenshot');
            $result['cookie'] = [
                'name' => $this->_getSession()->getName(),
                'value' => $this->_getSession()->getSessionId(),
                'lifetime' => $this->_getSession()->getCookieLifetime(),
                'path' => $this->_getSession()->getCookiePath(),
                'domain' => $this->_getSession()->getCookieDomain(),
            ];
        } catch (\Exception $exception) {
            $result = ['error' => $exception->getMessage(), 'errorcode' => $exception->getCode()];
        }

        return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData($result);
    }
}
