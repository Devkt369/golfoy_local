<?php
/**
 * Copyright © 2018 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\Webpos\Plugin\Elasticsearch\SearchAdapter\Query\Builder;

use Magento\Elasticsearch\SearchAdapter\Query\Builder\Match as MatchCore;
use Magento\Framework\Search\Request\QueryInterface as RequestQueryInterface;

/**
 * Class Match
 *
 * Plugin match elastic search to check condition and add partial search
 */
class MatchPlugin
{
    /**
     * After build to change query match
     *
     * @param MatchCore $subject
     * @param array $result
     * @param array $selectQuery
     * @param RequestQueryInterface $requestQuery
     * @param string $conditionType
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterBuild(
        MatchCore $subject,
        array $result,
        array $selectQuery,
        RequestQueryInterface $requestQuery,
        string $conditionType
    ) {
        if ($requestQuery->getName() !== 'webpos_search_key') {
            return $result;
        }

        if (isset($result['bool']['should'])) {
            $fieldArrayMatches = $result['bool']['should'];
            $fieldArrayMatchPrefix = [];
            foreach ($fieldArrayMatches as $fieldArrayMatch) {
                if (isset($fieldArrayMatch['match'])) {
                    $fieldArrayMatch['match_phrase_prefix'] = $fieldArrayMatch['match'];
                    unset($fieldArrayMatch['match']);
                }
                $fieldArrayMatchPrefix[] = $fieldArrayMatch;
            }
            $result['bool']['should'] = $fieldArrayMatchPrefix;
        }
        return $result;
    }
}
