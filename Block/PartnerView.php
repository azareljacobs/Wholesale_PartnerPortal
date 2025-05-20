<?php
declare(strict_types=1);

namespace Wholesale\PartnerPortal\Block;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Psr\Log\LoggerInterface;
use Wholesale\PartnerPortal\Api\Data\PartnerInterface;
use Wholesale\PartnerPortal\Api\PartnerRepositoryInterface;
use Wholesale\PartnerPortal\Model\Partner;
use Wholesale\PartnerPortal\Model\Service\PartnerMediaUrlService;

class PartnerView extends Template implements IdentityInterface
{
    /**
     * @var PartnerRepositoryInterface
     */
    private $partnerRepository;

    /**
     * @var PartnerInterface|null
     */
    private $partner = null;

    /**
     * @var LoggerInterface
     */
    private $logger;
    
    /**
     * @var Registry
     */
    private $registry;
    
    /**
     * @var PartnerMediaUrlService
     */
    private $mediaUrlService;

    /**
     * @param Context $context
     * @param PartnerRepositoryInterface $partnerRepository
     * @param Registry $registry
     * @param LoggerInterface $logger
     * @param PartnerMediaUrlService $mediaUrlService
     * @param array $data
     */
    public function __construct(
        Context $context,
        PartnerRepositoryInterface $partnerRepository,
        Registry $registry,
        LoggerInterface $logger,
        PartnerMediaUrlService $mediaUrlService,
        array $data = []
    ) {
        $this->partnerRepository = $partnerRepository;
        $this->registry = $registry;
        $this->logger = $logger;
        $this->mediaUrlService = $mediaUrlService;
        parent::__construct($context, $data);
    }

    /**
     * Get current partner
     *
     * @return PartnerInterface|null
     */
    public function getPartner()
    {
        if ($this->partner === null) {
            $slug = $this->getRequest()->getParam('slug');
            
            try {
                if ($slug) {
                    // Use the repository method that checks for active status
                    $this->partner = $this->partnerRepository->getActiveBySlug($slug);
                } else {
                    // Try to get from registry if it was set in the controller
                    $this->partner = $this->registry->registry('current_partner');
                }
            } catch (NoSuchEntityException $e) {
                $this->partner = null;
            }
        }
        
        return $this->partner;
    }

    /**
     * Check if partner exists
     *
     * @return bool
     */
    public function hasPartner()
    {
        return $this->getPartner() !== null;
    }

    /**
     * Get partner logo URL
     *
     * @return string|null
     */
    public function getLogoUrl()
    {
        return $this->mediaUrlService->getLogoUrl($this->getPartner());
    }
    
    /**
     * Get fallback logo URL if partner logo is missing
     *
     * @return string|null
     */
    public function getFallbackLogoUrl()
    {
        return $this->mediaUrlService->getFallbackLogoUrl();
    }
    
    /**
     * Get sanitized partner description
     *
     * @return string
     */
    /**
     * Get sanitized partner description
     * Ensures the description is properly sanitized for display
     *
     * @return string
     */
    public function getSanitizedDescription()
    {
        $partner = $this->getPartner();
        if (!$partner) {
            return '';
        }
        
        // Return the already sanitized description from the partner model
        // The sanitation happens at save time via PartnerDataSanitizerService
        return $partner->getDescription();
    }
    
    /**
     * Get identities for cache invalidation
     *
     * @return string[]
     */
    public function getIdentities()
    {
        $identities = [Partner::CACHE_TAG];
        
        $partner = $this->getPartner();
        if ($partner) {
            $identities = array_merge($identities, $partner->getIdentities());
        }
        
        return $identities;
    }
}