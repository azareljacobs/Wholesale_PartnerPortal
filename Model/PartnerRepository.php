<?php
/**
 * Partner repository implementation
 *
 * @category  Wholesale
 * @package   Wholesale_PartnerPortal
 */

namespace Wholesale\PartnerPortal\Model;

use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Wholesale\PartnerPortal\Api\Data\PartnerInterface;
use Wholesale\PartnerPortal\Api\Data\PartnerSearchResultsInterface;
use Wholesale\PartnerPortal\Api\Data\PartnerSearchResultsInterfaceFactory;
use Wholesale\PartnerPortal\Api\PartnerRepositoryInterface;
use Wholesale\PartnerPortal\Model\ResourceModel\Partner as PartnerResource;
use Wholesale\PartnerPortal\Model\ResourceModel\Partner\CollectionFactory;
use Wholesale\PartnerPortal\Model\Service\PartnerVisibilityService;

/**
 * Class PartnerRepository
 */
class PartnerRepository implements PartnerRepositoryInterface
{
    /**
     * @var PartnerResource
     */
    private $resource;

    /**
     * @var PartnerFactory
     */
    private $partnerFactory;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var PartnerSearchResultsInterfaceFactory
     */
    private $searchResultsFactory;

    /**
     * @var CollectionProcessorInterface
     */
    private $collectionProcessor;

    /**
     * @var array
     */
    private $partnerCache = [];
    
    /**
     * @var PartnerVisibilityService
     */
    private $visibilityService;

    /**
     * @param PartnerResource $resource
     * @param PartnerFactory $partnerFactory
     * @param CollectionFactory $collectionFactory
     * @param PartnerSearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     * @param PartnerVisibilityService $visibilityService
     */
    public function __construct(
        PartnerResource $resource,
        PartnerFactory $partnerFactory,
        CollectionFactory $collectionFactory,
        PartnerSearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor,
        PartnerVisibilityService $visibilityService
    ) {
        $this->resource = $resource;
        $this->partnerFactory = $partnerFactory;
        $this->collectionFactory = $collectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
        $this->visibilityService = $visibilityService;
    }

    /**
     * Save partner
     *
     * @param PartnerInterface $partner
     * @return PartnerInterface
     * @throws CouldNotSaveException
     */
    public function save(PartnerInterface $partner): PartnerInterface
    {
        try {
            $this->resource->save($partner);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }
        return $partner;
    }

    /**
     * Get partner by ID
     *
     * @param int $partnerId
     * @return PartnerInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $partnerId): PartnerInterface
    {
        // Check cache first
        $cacheKey = 'id_' . $partnerId;
        if (isset($this->partnerCache[$cacheKey])) {
            return $this->partnerCache[$cacheKey];
        }

        $partner = $this->partnerFactory->create();
        $this->resource->load($partner, $partnerId);
        if (!$partner->getId()) {
            throw new NoSuchEntityException(__('Partner with id "%1" does not exist.', $partnerId));
        }
        
        // Cache the result
        $this->partnerCache[$cacheKey] = $partner;
        
        return $partner;
    }

    /**
     * Get active partner by ID
     *
     * @param int $partnerId
     * @return PartnerInterface
     * @throws NoSuchEntityException
     */
    public function getActiveById(int $partnerId): PartnerInterface
    {
        $partner = $this->getById($partnerId);
        return $this->visibilityService->validateVisibility($partner, 'ID', $partnerId);
    }

    /**
     * Get partner by slug
     *
     * @param string $slug
     * @return PartnerInterface
     * @throws NoSuchEntityException
     */
    public function getBySlug(string $slug): PartnerInterface
    {
        // Check cache first
        $cacheKey = 'slug_' . $slug;
        if (isset($this->partnerCache[$cacheKey])) {
            return $this->partnerCache[$cacheKey];
        }

        $partner = $this->partnerFactory->create();
        $this->resource->load($partner, $slug, 'slug');
        if (!$partner->getId()) {
            throw new NoSuchEntityException(__('Partner with slug "%1" does not exist.', $slug));
        }
        
        // Cache the result
        $this->partnerCache[$cacheKey] = $partner;
        // Also cache by ID for potential future lookups
        $this->partnerCache['id_' . $partner->getId()] = $partner;
        
        return $partner;
    }

    /**
     * Get active partner by slug
     *
     * @param string $slug
     * @return PartnerInterface
     * @throws NoSuchEntityException
     */
    public function getActiveBySlug(string $slug): PartnerInterface
    {
        $partner = $this->getBySlug($slug);
        return $this->visibilityService->validateVisibility($partner, 'slug', $slug);
    }

    /**
     * Delete partner
     *
     * @param PartnerInterface $partner
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(PartnerInterface $partner): bool
    {
        try {
            $this->resource->delete($partner);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }
        return true;
    }

    /**
     * Delete partner by ID
     *
     * @param int $partnerId
     * @return bool
     * @throws NoSuchEntityException
     * @throws CouldNotDeleteException
     */
    public function deleteById(int $partnerId): bool
    {
        return $this->delete($this->getById($partnerId));
    }

    /**
     * Get partner list
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return \Wholesale\PartnerPortal\Api\Data\PartnerSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): \Wholesale\PartnerPortal\Api\Data\PartnerSearchResultsInterface
    {
        $collection = $this->collectionFactory->create();
        
        $this->collectionProcessor->process($searchCriteria, $collection);
        
        /** @var PartnerSearchResultsInterface $searchResults */
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());
        
        return $searchResults;
    }

    /**
     * Get active partners list
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return \Wholesale\PartnerPortal\Api\Data\PartnerSearchResultsInterface
     */
    public function getActiveList(SearchCriteriaInterface $searchCriteria): \Wholesale\PartnerPortal\Api\Data\PartnerSearchResultsInterface
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter(PartnerInterface::IS_ACTIVE, true);
        
        $this->collectionProcessor->process($searchCriteria, $collection);
        
        /** @var PartnerSearchResultsInterface $searchResults */
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());
        
        return $searchResults;
    }
}