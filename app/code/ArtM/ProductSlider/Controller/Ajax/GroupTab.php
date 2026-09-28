<?php
declare(strict_types=1);

namespace ArtM\ProductSlider\Controller\Ajax;

use ArtM\ProductSlider\Block\Group;
use ArtM\ProductSlider\Model\Frontend\GroupDataProvider;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\View\LayoutFactory;

class GroupTab extends Action
{
    public function __construct(
        Context $context,
        private readonly JsonFactory $jsonFactory,
        private readonly GroupDataProvider $groupDataProvider,
        private readonly LayoutFactory $layoutFactory
    ) {
        parent::__construct($context);
    }

    /**
     * Return rendered HTML for a single group tab (lazy-loaded).
     */
    public function execute(): Json
    {
        $result = $this->jsonFactory->create();

        try {
            $identifier = trim((string)$this->getRequest()->getParam('identifier', ''));
            $groupId = (int)$this->getRequest()->getParam('group_id', 0);
            $tabIndex = max(0, (int)$this->getRequest()->getParam('tab_index', 0));

            $group = $this->groupDataProvider->getGroup($identifier, $groupId);
            if (!$group) {
                return $result->setData([
                    'success' => false,
                    'message' => 'Group not found or not visible for this context.',
                ]);
            }

            $tabs = is_array($group['tabs'] ?? null) ? (array)$group['tabs'] : [];
            if (!isset($tabs[$tabIndex])) {
                return $result->setData([
                    'success' => false,
                    'message' => 'Tab not found.',
                ]);
            }

            $layout = $this->layoutFactory->create();
            $groupBlock = $layout->createBlock(Group::class);
            if (!$groupBlock) {
                throw new \RuntimeException('Unable to initialize group block.');
            }

            $html = $groupBlock->getTabHtml((array)$tabs[$tabIndex]);
            $this->getResponse()->setHeader('Cache-Control', 'public, max-age=300, s-maxage=900', true);

            return $result->setData([
                'success' => true,
                'data' => [
                    'tab_index' => $tabIndex,
                    'html' => $html,
                ],
            ]);
        } catch (\Throwable $exception) {
            return $result->setData([
                'success' => false,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
