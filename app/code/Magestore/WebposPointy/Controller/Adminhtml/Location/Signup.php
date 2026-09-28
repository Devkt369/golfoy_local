<?php
/**
 * Copyright © Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\WebposPointy\Controller\Adminhtml\Location;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magestore\WebposPointy\Api\PointyManagementInterface;

/**
 * Controller signup pointy
 */
class Signup extends Action implements HttpGetActionInterface
{
    protected $resource = 'Magestore_Webpos::locations';

    /**
     * @var PointyManagementInterface
     */
    private $pointyManagement;

    /**
     * Signup constructor.
     * @param PointyManagementInterface $pointyManagement
     * @param Context $context
     */
    public function __construct(
        PointyManagementInterface $pointyManagement,
        Context $context
    ) {
        $this->pointyManagement = $pointyManagement;
        parent::__construct($context);
    }

    /**
     * Execute
     *
     * @return Redirect|ResponseInterface|ResultInterface
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');

        try {
            $result = $this->pointyManagement->signupLocation($id);
            if ($result['has_error']) {
                $this->messageManager->addErrorMessage($result['message']);
            } else {
                $this->messageManager->addSuccessMessage('Location has been signed up successfully!');
            }
        } catch (Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        return $this->redirectResult('webposadmin/location_location/edit', ['id' => $id]);
    }

    /**
     * Redirect result
     *
     * @param string $url
     * @param array $params
     * @return Redirect
     */
    public function redirectResult($url, $params = [])
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath($url, $params);

        return $resultRedirect;
    }

    /**
     * Is allow
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed($this->resource);
    }
}
