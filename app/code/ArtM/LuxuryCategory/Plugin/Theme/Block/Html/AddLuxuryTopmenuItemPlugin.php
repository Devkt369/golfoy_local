<?php
declare(strict_types=1);

namespace ArtM\LuxuryCategory\Plugin\Theme\Block\Html;

use ArtM\LuxuryCategory\Service\LuxuryModeService;
use ArtM\LuxuryCategory\Model\ResourceModel\LuxuryProductChecker;
use Magento\Framework\Data\Tree\Node;
use Magento\Theme\Block\Html\Topmenu;

/**
 * Walks the already-built category menu tree (populated moments earlier by
 * Magento_Catalog's own `catalogTopmenu` plugin, see di.xml sortOrder) and
 * inserts a "Luxe" child node under every category node whose category
 * contains at least one luxury=1 product.
 *
 * Does not create categories, does not duplicate the tree, does not touch
 * any node it doesn't need to. A single bulk query resolves which of the
 * visited categories qualify, so this stays cheap regardless of catalog
 * size (see LuxuryProductChecker).
 */
class AddLuxuryTopmenuItemPlugin
{
    private const NODE_ID_PREFIX = 'category-node-';
    private const LUXE_NODE_SUFFIX = '-luxe';

    /** Added to a category's own li when it has a "Luxe" child link. */
    private const HAS_LUXE_CLASS = 'have_luxe';

    /** Added to the injected "Luxe" li itself. */
    private const LUXE_ITEM_CLASS = 'luxe_category';

    public function __construct(
        private readonly LuxuryModeService $luxuryModeService,
        private readonly LuxuryProductChecker $luxuryProductChecker
    ) {
    }

    /**
     * @param Topmenu $subject
     * @param string $outermostClass
     * @param string $childrenWrapClass
     * @param int $limit
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeGetHtml(
        Topmenu $subject,
        $outermostClass = '',
        $childrenWrapClass = '',
        $limit = 0
    ): void {
        $rootNode = $subject->getMenu();
        if ($rootNode === null) {
            return;
        }

        $categoryNodes = $this->collectCategoryNodes($rootNode);
        if ($categoryNodes === []) {
            return;
        }

        $categoryIds = array_map(
            static fn(Node $node): int => self::extractCategoryId($node),
            $categoryNodes
        );

        $luxuryCategoryIds = $this->luxuryProductChecker->getCategoryIdsWithLuxuryProducts($categoryIds);
        if ($luxuryCategoryIds === []) {
            return;
        }

        $luxuryCategoryIdMap = array_flip($luxuryCategoryIds);

        foreach ($categoryNodes as $categoryNode) {
            $categoryId = self::extractCategoryId($categoryNode);
            if (!isset($luxuryCategoryIdMap[$categoryId])) {
                continue;
            }

            $this->appendLuxeNode($categoryNode, $categoryId);
        }
    }

    /**
     * Recursively flattens the tree into a list of category-only nodes.
     *
     * @return Node[]
     */
    private function collectCategoryNodes(Node $node): array
    {
        $result = [];

        foreach ($node->getChildren() as $childNode) {
            /** @var Node $childNode */
            $childId = (string)$childNode->getId();

            // Never treat a "Luxe" node we injected on an earlier
            // beforeGetHtml() call (e.g. a theme rendering the menu block
            // more than once against the same tree) as a real category —
            // its id also starts with NODE_ID_PREFIX, so without this
            // check it would be re-collected, re-processed, and walked
            // into on every subsequent render.
            if (str_ends_with($childId, self::LUXE_NODE_SUFFIX)) {
                continue;
            }

            if (str_starts_with($childId, self::NODE_ID_PREFIX)) {
                $result[] = $childNode;
            }

            $result = array_merge($result, $this->collectCategoryNodes($childNode));
        }

        return $result;
    }

    /**
     * Builds and inserts the "Luxe" child node as the first child of the
     * given category node.
     */
    private function appendLuxeNode(Node $categoryNode, int $categoryId): void
    {
        $tree = $categoryNode->getTree();
        $nodeId = self::NODE_ID_PREFIX . $categoryId . self::LUXE_NODE_SUFFIX;

        // Never insert twice (defensive — e.g. if a theme renders the menu
        // block more than once per request without a fresh tree).
        if ($tree->getNodeById($nodeId) !== null) {
            return;
        }

        $this->addClass($categoryNode, self::HAS_LUXE_CLASS);

        $luxeNode = new Node(
            [
                'name' => __('Luxe'),
                'id' => $nodeId,
                'url' => $this->luxuryModeService->appendLuxuryToUrl((string)$categoryNode->getData('url')),
                'is_category' => true,
                'class' => self::LUXE_ITEM_CLASS,
            ],
            'id',
            $tree,
            $categoryNode
        );

        $categoryNode->addChild($luxeNode);
    }

    /**
     * Appends $class to the node's existing class list (Node::getClass()/
     * setClass() are the DataObject magic accessors for the 'class' data
     * key, which Topmenu::_getMenuItemClasses() renders into the li's
     * class attribute) rather than overwriting it, since core Magento
     * also uses setClass() on the same node later (e.g. the outermost
     * "level-top" class for top-level items) and both need to survive.
     */
    private function addClass(Node $node, string $class): void
    {
        $classes = array_filter(explode(' ', (string)$node->getClass()));
        if (!in_array($class, $classes, true)) {
            $classes[] = $class;
        }

        $node->setClass(implode(' ', $classes));
    }

    /**
     * Extracts the numeric category id from a `category-node-<id>` node id.
     */
    private static function extractCategoryId(Node $node): int
    {
        $id = (string)$node->getId();

        return (int)str_replace(self::NODE_ID_PREFIX, '', $id);
    }
}
