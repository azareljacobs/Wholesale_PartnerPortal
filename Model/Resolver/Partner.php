<?php
/**
 * Partner GraphQL resolver
 *
 * @category  Wholesale
 * @package   Wholesale_PartnerPortal
 */

namespace Wholesale\PartnerPortal\Model\Resolver;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Wholesale\PartnerPortal\Api\PartnerRepositoryInterface;
use Wholesale\PartnerPortal\Model\Service\PartnerQueryService;

class Partner implements ResolverInterface
{
    /**
     * @var PartnerRepositoryInterface
     */
    private $partnerRepository;
    
    /**
     * @var PartnerQueryService
     */
    private $queryService;

    /**
     * @param PartnerRepositoryInterface $partnerRepository
     * @param PartnerQueryService $queryService
     */
    public function __construct(
        PartnerRepositoryInterface $partnerRepository,
        PartnerQueryService $queryService
    ) {
        $this->partnerRepository = $partnerRepository;
        $this->queryService = $queryService;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if (empty($args['id']) && empty($args['slug'])) {
            throw new GraphQlInputException(__('You must specify either a partner ID or slug.'));
        }

        try {
            $partner = null;
            if (!empty($args['id'])) {
                $partner = $this->queryService->getById((int)$args['id'], true);
            } elseif (!empty($args['slug'])) {
                $partner = $this->queryService->getBySlug($args['slug'], true);
            }

            return [
                'partner_id' => $partner->getId(),
                'name' => $partner->getName(),
                'slug' => $partner->getSlug(),
                'logo' => $partner->getLogo(),
                'description' => $partner->getDescription(),
                'website' => $partner->getWebsite(),
                'contact_email' => $partner->getContactEmail(),
                'is_active' => (bool)$partner->getIsActive()
            ];
        } catch (NoSuchEntityException $e) {
            throw new GraphQlNoSuchEntityException(__($e->getMessage()), $e);
        }
    }
}