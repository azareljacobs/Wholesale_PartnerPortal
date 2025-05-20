<?php
/**
 * Partner visibility service
 *
 * @category  Wholesale
 * @package   Wholesale_PartnerPortal
 */

namespace Wholesale\PartnerPortal\Model\Service;

use Wholesale\PartnerPortal\Api\Data\PartnerInterface;

/**
 * Service class to handle partner visibility rules
 */
class PartnerVisibilityService
{
    /**
     * Check if a partner is visible
     *
     * @param PartnerInterface $partner
     * @return bool
     */
    public function isVisible(PartnerInterface $partner): bool
    {
        // Currently only checking active status, but this could be extended with
        // additional business rules such as checking dates, customer group visibility, etc.
        return (bool)$partner->getIsActive();
    }

    /**
     * Validate partner is visible or throw exception
     *
     * @param PartnerInterface $partner
     * @param string $identifierType Type of identifier used (id, slug, etc.)
     * @param string $identifier The actual identifier value
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @return PartnerInterface
     */
    public function validateVisibility(
        PartnerInterface $partner,
        string $identifierType,
        string $identifier
    ): PartnerInterface {
        if (!$this->isVisible($partner)) {
            throw new \Magento\Framework\Exception\NoSuchEntityException(
                __('Partner with %1 "%2" is not active.', $identifierType, $identifier)
            );
        }
        
        return $partner;
    }
}