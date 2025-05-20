<?php
declare(strict_types=1);

namespace Wholesale\PartnerPortal\ViewModel;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Psr\Log\LoggerInterface;
use Wholesale\PartnerPortal\Api\Data\PartnerInterface;
use Wholesale\PartnerPortal\Api\PartnerRepositoryInterface;
use Wholesale\PartnerPortal\Model\Service\PartnerMediaUrlService;

class Partner implements ArgumentInterface
{
    /**
     * @var PartnerRepositoryInterface
     */
    private $partnerRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var PartnerMediaUrlService
     */
    private $mediaUrlService;

    /**
     * @var PartnerInterface|null
     */
    private $partner = null;

    /**
     * @param PartnerRepositoryInterface $partnerRepository
     * @param LoggerInterface $logger
     * @param PartnerMediaUrlService $mediaUrlService
     */
    public function __construct(
        PartnerRepositoryInterface $partnerRepository,
        LoggerInterface $logger,
        PartnerMediaUrlService $mediaUrlService
    ) {
        $this->partnerRepository = $partnerRepository;
        $this->logger = $logger;
        $this->mediaUrlService = $mediaUrlService;
    }

    /**
     * Get partner by slug
     *
     * @param string|null $slug
     * @return PartnerInterface|null
     */
    public function getPartnerBySlug(?string $slug): ?PartnerInterface
    {
        if (!$slug) {
            return null;
        }

        try {
            // Use the repository method that checks for active status
            return $this->partnerRepository->getActiveBySlug($slug);
        } catch (NoSuchEntityException $e) {
            $this->logger->error('Partner not found by slug: ' . $slug);
            return null;
        }
    }

    /**
     * Get partner logo URL
     *
     * @param PartnerInterface|null $partner
     * @return string|null
     */
    public function getLogoUrl(?PartnerInterface $partner): ?string
    {
        if (!$partner) {
            return null;
        }
        
        return $this->mediaUrlService->getLogoUrl($partner);
    }
    
    /**
     * Get fallback logo URL if partner logo is missing
     *
     * @return string|null
     */
    public function getFallbackLogoUrl(): ?string
    {
        return $this->mediaUrlService->getFallbackLogoUrl();
    }
}