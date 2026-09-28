<?php
/**
 * Copyright © 2018 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\Webpos\Model\Staff;

use Exception;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magestore\Appadmin\Model\ResourceModel\Staff\AuthorizationRule\CollectionFactory;
use Magestore\Webpos\Api\Data\Staff\SessionInterface;
use Magestore\Webpos\Api\Data\Staff\SessionInterfaceFactory;
use Magestore\Webpos\Api\Data\Staff\SessionSearchResultsInterface;
use Magestore\Webpos\Api\Data\Staff\SessionSearchResultsInterfaceFactory;
use Magestore\Appadmin\Api\DataProvider\GetStaffByIdInterface;
use Magestore\Webpos\Api\Staff\SessionRepositoryInterface;
use Magestore\Webpos\Model\ResourceModel\Staff\Session\Collection;

/**
 * Staff SessionRepository
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SessionRepository implements SessionRepositoryInterface
{
    /**
     * @var StaffFactory
     */
    protected $sessionFactory;
    /**
     * @var \Magestore\Webpos\Model\ResourceModel\Staff\Session
     */
    protected $sessionResource;
    /**
     * @var SessionSearchResultsInterface
     */
    protected $sessionSearchResults;
    /**
     * @var \Magestore\Webpos\Model\ResourceModel\Staff\Session\CollectionFactory
     */
    protected $sessionCollectionFactory;
    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var GetStaffByIdInterface
     */
    protected $getStaffById;

    /**
     * SessionRepository constructor.
     *
     * @param SessionInterfaceFactory $sessionFactory
     * @param \Magestore\Webpos\Model\ResourceModel\Staff\Session $sessionResource
     * @param \Magestore\Webpos\Model\ResourceModel\Staff\Session\CollectionFactory $sessionCollectionFactory
     * @param SessionSearchResultsInterfaceFactory $sessionSearchResultsInterfaceFactory
     * @param CollectionFactory $collectionFactory
     * @param GetStaffByIdInterface $getStaffById
     */
    public function __construct(
        SessionInterfaceFactory $sessionFactory,
        \Magestore\Webpos\Model\ResourceModel\Staff\Session $sessionResource,
        \Magestore\Webpos\Model\ResourceModel\Staff\Session\CollectionFactory $sessionCollectionFactory,
        SessionSearchResultsInterfaceFactory $sessionSearchResultsInterfaceFactory,
        CollectionFactory $collectionFactory,
        GetStaffByIdInterface $getStaffById
    ) {
        $this->sessionFactory = $sessionFactory;
        $this->sessionResource = $sessionResource;
        $this->sessionSearchResults = $sessionSearchResultsInterfaceFactory;
        $this->sessionCollectionFactory = $sessionCollectionFactory;
        $this->collectionFactory = $collectionFactory;
        $this->getStaffById = $getStaffById;
    }

    /**
     * @inheritdoc
     */
    public function save(SessionInterface $session)
    {
        try {
            /* @var Session $session */
            /* @var Session $sessionModel */
            $sessionModel = $this->sessionResource->save($session);
            return $sessionModel;
        } catch (Exception $e) {
            throw new CouldNotSaveException(__('Could not save session.'), $e);
        }
    }

    /**
     * @inheritdoc
     */
    public function getById($id)
    {
        $session = $this->sessionFactory->create();
        $this->sessionResource->load($session, $id);
        if (!$session->getId()) {
            throw new NoSuchEntityException(
                __('Session with id "%1" does not exist.', $id)
            );
        } else {
            return $session;
        }
    }

    /**
     * Retrieve Session.
     *
     * @param int $sessionId
     * @return SessionInterface
     * @throws LocalizedException
     */
    public function getBySessionId($sessionId)
    {
        $session = $this->sessionFactory->create();
        $this->sessionResource->load(
            $session,
            $sessionId,
            SessionInterface::SESSION_ID
        );
        if (!$session->getId()) {
            throw new NoSuchEntityException(
                __('Session with id "%1" does not exist.', $sessionId)
            );
        } else {
            return $session;
        }
    }

    /**
     * @inheritdoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria)
    {
        $collection = $this->sessionCollectionFactory->create();
        //Add filters from root filter group to the collection
        /** @var FilterGroup $group */
        foreach ($searchCriteria->getFilterGroups() as $filterGroup) {
            foreach ($filterGroup->getFilters() as $filter) {
                $condition = $filter->getConditionType() ? $filter->getConditionType() : 'eq';
                $collection->addFieldToFilter($filter->getField(), [$condition => $filter->getValue()]);
            }
        }
        $sortOrders = $searchCriteria->getSortOrders();
        if ($sortOrders === null) {
            $sortOrders = [];
        }
        /** @var SortOrder $sortOrder */
        foreach ($sortOrders as $sortOrder) {
            $field = $sortOrder->getField();
            $collection->addOrder(
                $field,
                ($sortOrder->getDirection() == SortOrder::SORT_ASC)
                    ? SortOrder::SORT_ASC
                    : SortOrder::SORT_DESC
            );
        }
        $collection->setCurPage($searchCriteria->getCurrentPage());
        $collection->setPageSize($searchCriteria->getPageSize());
        $collectionSize = $collection->getSize();
        $collection->load();
        $searchResults = $this->sessionSearchResults->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collectionSize);
        return $searchResults;
    }

    /**
     * @inheritdoc
     */
    public function getListByStaffId($staffId)
    {
        /** @var Collection $collection */
        $collection = $this->sessionCollectionFactory->create();
        $collection->addFieldToFilter('staff_id', $staffId);

        return $collection;
    }

    /**
     * @inheritdoc
     */
    public function delete(SessionInterface $session)
    {
        return $this->deleteById($session->getId());
    }

    /**
     * @inheritdoc
     */
    public function deleteById($sessionId)
    {
        /* @var Session $session */
        $session = $this->getById($sessionId);
        if ($session->getId()) {
            $this->sessionResource->delete($session);
            return true;
        } else {
            throw new NoSuchEntityException(
                __('Session with id "%1" does not exist.', $sessionId)
            );
        }
    }

    /**
     * Get All Current Permission
     *
     * @param int $staffId
     * @return array
     * @throws LocalizedException
     */
    public function getAllCurrentPermission($staffId)
    {
        $staffModel = $this->getStaffById->execute($staffId);
        $resourceAccess = [];
        if ($staffModel->getId()) {
            $roleId = $staffModel->getRoleId();
            $authorizationCollection = $this->collectionFactory->create()->addFieldToFilter('role_id', $roleId);
            foreach ($authorizationCollection as $resource) {
                $resourceAccess[] = $resource->getResourceId();
            }
        }
        return $resourceAccess;
    }

    /**
     * SignOutPos
     *
     * @param int $posId
     * @return Session|null
     * @throws LocalizedException
     */
    public function signOutPos($posId)
    {
        $sessionCollection = $this->sessionCollectionFactory->create()->addFieldToFilter('pos_id', $posId);
        foreach ($sessionCollection as $session) {
            $session->setData('pos_id', null);
            try {
                /* @var Session $session */
                /* @var Session $sessionModel */
                $session->getResource()->save($session);
                return $session;
            } catch (Exception $e) {
                throw new CouldNotSaveException(__('Could not save session.'), $e);
            }
        }
        return null;
    }
}
