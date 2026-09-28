<?php

namespace Magestore\Webpos\Model\Deploy;

use Magento\Authorization\Model\ResourceModel\Rules;
use Magestore\Webpos\Api\Console\WebposUpdateRoleInterface;

/**
 * Webpos Update Role Command class
 */
class WebposUpdateRole implements WebposUpdateRoleInterface
{
    /**
     * @var \Magento\Authorization\Model\RulesFactory
     */
    protected $rulesFactory;

    /**
     * @var Rules
     */
    protected $ruleResourceModel;

    /**
     * @var \Magento\Authorization\Model\ResourceModel\Rules\CollectionFactory $ruleCollection
     */
    protected $ruleCollection;

    /**
     * Constructor
     *
     * @param \Magento\Authorization\Model\RulesFactory $rulesFactory
     * @param Rules\CollectionFactory $ruleCollection
     * @param Rules $ruleResourceModel
     */
    public function __construct(
        \Magento\Authorization\Model\RulesFactory $rulesFactory,
        \Magento\Authorization\Model\ResourceModel\Rules\CollectionFactory $ruleCollection,
        Rules $ruleResourceModel
    ) {
        $this->ruleCollection = $ruleCollection;
        $this->rulesFactory = $rulesFactory;
        $this->ruleResourceModel = $ruleResourceModel;
    }

    /**
     * @inheritDoc
     */
    public function execute($output)
    {
        $ruleExistMagestore = $this->ruleCollection->create();
        $ruleExistMagestore->getSelect()->group('role_id');
        $ruleExistMagestore->addFieldToFilter('resource_id', ['like' => self::MAGESTORE_ROLE_SEARCH]);
        $ruleExistMagestore->getSelect()->orWhere(
            "
            resource_id = 'Magento_Backend::all' AND main_table.permission = 'allow'
            or
            resource_id = 'Magento_Backend::admin' AND main_table.permission = 'deny'
            "
        );
        $ruleExistMagestore->getSelect()->reset(\Zend_Db_Select::COLUMNS);
        $ruleExistMagestore->getSelect()->columns(['role_exclude_id' => 'role_id']);
        //Select not exist magestore roles
        $ruleNotExistMagestore = $this->ruleCollection->create();
        $ruleNotExistMagestore->getSelect()->group('role_id');
        $ruleNotExistMagestore->addFieldToFilter('role_id', ['nin' => $ruleExistMagestore->getSelect()]);
        $ruleNotExistMagestore->getSelect()->reset(\Zend_Db_Select::COLUMNS);
        $ruleNotExistMagestore->getSelect()->columns(['role_id' => 'role_id']);
        foreach ($ruleNotExistMagestore->getItems() as $rule) {
            $rulesModel = $this->rulesFactory->create()->setRoleId($rule->getData('role_id'));
            $oldResources = $this->getResourcesByRoleId($rule->getData('role_id'));
            $rulesModel->setResources($oldResources);
            if ($rulesModel->getResources()) {
                $rulesModel->saveRel();
            }
        }
        $output->writeln('Update Roles Resource Successfully Completed!!!');
    }

    /**
     * Get old resources
     *
     * @param int $roleId
     * @return array
     */
    public function getResourcesByRoleId(int $roleId) : array
    {
        $ruleCollection = $this->ruleCollection->create();
        $ruleCollection->addFieldToFilter('role_id', ['eq' => $roleId]);
        $ruleCollection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
        $ruleCollection->getSelect()->columns(['resource_id' => 'resource_id']);
        return $ruleCollection->getColumnValues('resource_id');
    }
}
