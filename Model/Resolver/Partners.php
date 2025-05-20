<?php
/**
 * Partners collection GraphQL resolver
 *
 * @category  Wholesale
 * @package   Wholesale_PartnerPortal
 */

namespace Wholesale\PartnerPortal\Model\Resolver;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\FilterGroupBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Wholesale\PartnerPortal\Api\PartnerRepositoryInterface;
use Wholesale\PartnerPortal\Api\Data\PartnerInterface;
use Wholesale\PartnerPortal\Model\Service\PartnerVisibilityService;

class Partners implements ResolverInterface
{
    /**
     * @var PartnerRepositoryInterface
     */
    private $partnerRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var FilterBuilder
     */
    private $filterBuilder;

    /**
     * @var FilterGroupBuilder
     */
    private $filterGroupBuilder;

    /**
     * @var SortOrderBuilder
     */
    private $sortOrderBuilder;

    /**
     * @param PartnerRepositoryInterface $partnerRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param FilterBuilder $filterBuilder
     * @param FilterGroupBuilder $filterGroupBuilder
     * @param SortOrderBuilder $sortOrderBuilder
     * @param PartnerVisibilityService $visibilityService
     */
    public function __construct(
        PartnerRepositoryInterface $partnerRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        FilterBuilder $filterBuilder,
        FilterGroupBuilder $filterGroupBuilder,
        SortOrderBuilder $sortOrderBuilder,
        PartnerVisibilityService $visibilityService
    ) {
        $this->partnerRepository = $partnerRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder = $filterBuilder;
        $this->filterGroupBuilder = $filterGroupBuilder;
        $this->sortOrderBuilder = $sortOrderBuilder;
        $this->visibilityService = $visibilityService;
    }

    /**
     * @inheritdoc
     */
    /**
     * Resolve partners query
     *
     * @param Field $field GraphQL field
     * @param mixed $context Context object
     * @param ResolveInfo $info Resolve info
     * @param array|null $value Field value
     * @param array|null $args Query arguments
     * @return array Partner collection with pagination info
     * @throws GraphQlInputException
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ): array {
        $this->validateArgs($args);
        
        // Apply custom filters if provided
        if (!empty($args['filter'])) {
            $this->addFilters($args['filter']);
        }
        
        // Apply sorting
        if (!empty($args['sort'])) {
            $this->addSorting($args['sort']);
        }
        
        // Apply pagination
        $this->searchCriteriaBuilder->setPageSize($args['pageSize']);
        $this->searchCriteriaBuilder->setCurrentPage($args['currentPage']);
        
        // Add the is_active filter using the visibility service's criteria
        // This ensures consistent filtering across all components
        $this->searchCriteriaBuilder->addFilter(
            PartnerInterface::IS_ACTIVE,
            true
        );
        
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $searchResults = $this->partnerRepository->getList($searchCriteria);
        
        $items = [];
        foreach ($searchResults->getItems() as $partner) {
            $items[] = [
                'partner_id' => $partner->getId(),
                'name' => $partner->getName(),
                'slug' => $partner->getSlug(),
                'logo' => $partner->getLogo(),
                'description' => $partner->getDescription(),
                'website' => $partner->getWebsite(),
                'contact_email' => $partner->getContactEmail(),
                'is_active' => (bool)$partner->getIsActive()
            ];
        }
        
        return [
            'items' => $items,
            'total_count' => $searchResults->getTotalCount(),
            'page_info' => [
                'page_size' => $args['pageSize'],
                'current_page' => $args['currentPage'],
                'total_pages' => ceil($searchResults->getTotalCount() / $args['pageSize'])
            ]
        ];
    }

    /**
     * Process and add filters to the search criteria
     *
     * @param array $filters Filters from GraphQL query
     * @return void
     */
    private function addFilters(array $filters): void
    {
        $filterFields = ['name', 'slug', 'website', 'is_active'];
        
        foreach ($filters as $field => $condition) {
            if ($field === 'or') {
                // Handle OR conditions separately
                continue;
            }
            
            if (!in_array($field, $filterFields)) {
                continue;
            }
            
            foreach ($condition as $condType => $value) {
                $this->searchCriteriaBuilder->addFilter($field, $value, $condType);
            }
        }
        
        // Handle OR conditions if provided
        if (isset($filters['or'])) {
            $orFilters = [];
            
            foreach ($filters['or'] as $field => $condition) {
                if (!in_array($field, $filterFields)) {
                    continue;
                }
                
                foreach ($condition as $condType => $value) {
                    $orFilters[] = $this->filterBuilder
                        ->setField($field)
                        ->setValue($value)
                        ->setConditionType($condType)
                        ->create();
                }
            }
            
            if (!empty($orFilters)) {
                $orFilterGroup = $this->filterGroupBuilder->setFilters($orFilters)->create();
                $this->searchCriteriaBuilder->addFilterGroup($orFilterGroup);
            }
        }
    }

    /**
     * Add sorting to the search criteria
     *
     * @param array $sortArgs Sorting arguments from GraphQL query
     * @return void
     */
    private function addSorting(array $sortArgs): void
    {
        $sortOrders = [];
        
        foreach ($sortArgs as $field => $direction) {
            $sortOrders[] = $this->sortOrderBuilder
                ->setField($field)
                ->setDirection($direction === 'ASC' ? SortOrder::SORT_ASC : SortOrder::SORT_DESC)
                ->create();
        }
        
        if (!empty($sortOrders)) {
            $this->searchCriteriaBuilder->setSortOrders($sortOrders);
        }
    }

    /**
     * Validate input arguments
     *
     * @param array $args Query arguments
     * @throws GraphQlInputException If validation fails
     * @return void
     */
    private function validateArgs(array $args): void
    {
        if (isset($args['pageSize']) && $args['pageSize'] < 1) {
            throw new GraphQlInputException(__('pageSize must be greater than 0.'));
        }
        
        if (isset($args['currentPage']) && $args['currentPage'] < 1) {
            throw new GraphQlInputException(__('currentPage must be greater than 0.'));
        }
    }
}