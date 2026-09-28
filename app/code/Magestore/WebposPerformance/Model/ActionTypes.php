<?php
/**
 * Copyright © 2020 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\WebposPerformance\Model;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class ActionTypes
 *
 * Use for action types
 */
class ActionTypes implements OptionSourceInterface
{
    /**
     * @var array
     */
    protected $criticalAction;

    /**
     * @var array
     */
    protected $highPriorityAction;

    /**
     * @var array
     */
    protected $lowPriorityAction;

    /**
     * ActionTypes constructor.
     *
     * @param array $criticalAction
     * @param array $highPriorityAction
     * @param array $lowPriorityAction
     */
    public function __construct(
        array $criticalAction = [],
        array $highPriorityAction = [],
        array $lowPriorityAction = []
    ) {
        $this->criticalAction = $criticalAction;
        $this->highPriorityAction = $highPriorityAction;
        $this->lowPriorityAction = $lowPriorityAction;
    }

    /**
     * Get Data
     *
     * @return array
     */
    public function getOptionData()
    {
        return array_merge(
            $this->getCriticalAction(),
            $this->getHighPriorityAction(),
            $this->getLowPriorityAction()
        );
    }

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = $this->getOptionData();
        $array = [];
        foreach ($options as $key => $value) {
            $array[] = [
                'label' => $value,
                'value' =>  $key
            ];
        }
        return $array;
    }

    /**
     * Get critical action
     *
     * @return array
     */
    public function getCriticalAction()
    {
        return $this->criticalAction;
    }

    /**
     * Get high priority
     *
     * @return array
     */
    public function getHighPriorityAction()
    {
        return $this->highPriorityAction;
    }

    /**
     * Get low priority
     *
     * @return array
     */
    public function getLowPriorityAction()
    {
        return $this->lowPriorityAction;
    }

    /**
     * Get action label
     *
     * @param string $actionCode
     * @return string
     */
    public function getActionLabel(string $actionCode)
    {
        $options = $this->getOptionData();
        if (isset($options[$actionCode])) {
            return $options[$actionCode];
        } else {
            return '';
        }
    }
}
