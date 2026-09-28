<?php

namespace ArtM\PageGroups\Controller\Index;

use ArtM\PageGroups\Model\PageGroup;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

class Data extends Action
{
    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var PageGroup
     */
    private $pageGroup;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        PageGroup $pageGroup
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->pageGroup = $pageGroup;
    }

    /**
     * Public endpoint to fetch a group with sorted images and attribute values.
     *
     * Query params:
     *  group_id (optional)
     *  page (optional page identifier)
     *  group (optional group title)
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();

        try {
            $groupId = (int)$this->getRequest()->getParam('group_id');
            $pageIdentifier = trim((string)$this->getRequest()->getParam('page', ''));
            $groupTitle = trim((string)$this->getRequest()->getParam('group', ''));

            $group = null;
            if ($groupId > 0) {
                $group = $this->pageGroup->getGroupDataById($groupId);
            } elseif ($pageIdentifier !== '' && $groupTitle !== '') {
                $group = $this->pageGroup->getGroupData($pageIdentifier, $groupTitle);
            } else {
                return $resultJson->setData([
                    'success' => false,
                    'message' => __('Provide either group_id OR both page and group.'),
                ]);
            }

            if (!$group) {
                return $resultJson->setData([
                    'success' => false,
                    'message' => __('Group not found.'),
                ]);
            }

            return $resultJson->setData([
                'success' => true,
                'group' => $group,
            ]);
        } catch (\Throwable $e) {
            return $resultJson->setData([
                'success' => false,
                'message' => __('Unable to load embed data: %1', $e->getMessage()),
            ]);
        }
    }
}
