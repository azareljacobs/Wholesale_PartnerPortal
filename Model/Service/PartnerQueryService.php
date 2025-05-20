<?php
declare(strict_types=1);
/**
 * Partner query service
 *
 * @category  Wholesale
 * @package   Wholesale_PartnerPortal
 */

namespace Wholesale\PartnerPortal\Model\Service;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;
use Wholesale\PartnerPortal\Api\Data\PartnerInterface;
use Wholesale\PartnerPortal\Api\Data\PartnerSearchResultsInterface;
use Wholesale\PartnerPortal\Api\PartnerRepositoryInterface;

/**
 * Service class to handle partner queries (find, get, list)
 * Following Command/Query Separation Pattern
 */
class PartnerQueryService
{
    /**
     * @var PartnerRepositoryInterface
     */
    private $partnerRepository;

    /**
     * @var PartnerVisibilityService
     */
    private $visibilityService;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var SortOrderBuilder
     */
    private $sortOrderBuilder;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param PartnerRepositoryInterface $partnerRepository
     * @param PartnerVisibilityService $visibilityService
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param SortOrderBuilder $sortOrderBuilder
     * @param LoggerInterface $logger
     */
    public function __construct(
        PartnerRepositoryInterface $partnerRepository,
        PartnerVisibilityService $visibilityService,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SortOrderBuilder $sortOrderBuilder,
        LoggerInterface $logger
    ) {
        $this->partnerRepository = $partnerRepository;
        $this->visibilityService = $visibilityService;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->sortOrderBuilder = $sortOrderBuilder;
        $this->logger = $logger;
    }

    /**
     * Get partner by ID
     *
     * @param int $partnerId
     * @param bool $requireActive Whether to require active status
     * @return PartnerInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $partnerId, bool $requireActive = false): PartnerInterface
    {
        $partner = $this->partnerRepository->getById($partnerId);
        
        if ($requireActive) {
            // Validate visibility if active status is required
            $partner = $this->visibilityService->validateVisibility($partner, 'ID', (string)$partnerId);
        }
        
        return $partner;
    }

    /**
     * Get partner by slug
     *
     * @param string $slug
     * @param bool $requireActive Whether to require active status
     * @return PartnerInterface
     * @throws NoSuchEntityException
     */
    public function getBySlug(string $slug, bool $requireActive = false): PartnerInterface
    {
        $partner = $this->partnerRepository->getBySlug($slug);
        
        if ($requireActive) {
            // Validate visibility if active status is required
            $partner = $this->visibilityService->validateVisibility($partner, 'slug', $slug);
        }
        
        return $partner;
    }

    /**
     * Get list of partners
     *
     * @param SearchCriteriaInterface|null $searchCriteria
     * @param bool $activeOnly Whether to return only active partners
     * @return PartnerSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria = null, bool $activeOnly = false): PartnerSearchResultsInterface
    {
        if ($searchCriteria === null) {
            // Create default search criteria if none provided
            $sortOrder = $this->sortOrderBuilder
                ->setField(PartnerInterface::NAME)
                ->setDirection('ASC')
                ->create();
                
            $searchCriteria = $this->searchCriteriaBuilder
                ->setSortOrders([$sortOrder])
                ->create();
        }
        
        // Use appropriate repository method based on activeOnly flag
        if ($activeOnly) {
            return $this->partnerRepository->getActiveList($searchCriteria);
        } else {
            return $this->partnerRepository->getList($searchCriteria);
        }
    }

    /**
     * Check if partner exists
     *
     * @param string $identifier Either ID or slug
     * @param string $type Type of identifier ('id' or 'slug')
     * @param bool $requireActive Whether to check only active partners
     * @return bool
     */
    public function exists(string $identifier, string $type = 'id', bool $requireActive = false): bool
    {
        try {
            if ($type === 'id') {
                $partner = $this->partnerRepository->getById((int)$identifier);
            } else {
                $partner = $this->partnerRepository->getBySlug($identifier);
            }
            
            if ($requireActive) {
                return $this->visibilityService->isVisible($partner);
            }
            
            return true;
        } catch (NoSuchEntityException $e) {
            return false;
        } catch (\Exception $e) {
            $this->logger->error('Error checking partner existence: ' . $e->getMessage());
            return false;
        }
    }
}