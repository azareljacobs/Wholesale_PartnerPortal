<?php
/**
 * Partner media URL service
 *
 * @category  Wholesale
 * @package   Wholesale_PartnerPortal
 */

namespace Wholesale\PartnerPortal\Model\Service;

use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Wholesale\PartnerPortal\Api\Data\PartnerInterface;

/**
 * Service class to handle partner media URL generation
 */
class PartnerMediaUrlService
{
    /**
     * Base media path for partner logos
     */
    private const BASE_MEDIA_PATH = 'wholesale/partner';

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param StoreManagerInterface $storeManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        LoggerInterface $logger
    ) {
        $this->storeManager = $storeManager;
        $this->logger = $logger;
    }

    /**
     * Get partner logo URL
     *
     * @param PartnerInterface|null $partner
     * @return string|null
     */
    public function getLogoUrl(PartnerInterface $partner = null): ?string
    {
        if (!$partner || !$partner->getLogo()) {
            return null;
        }

        try {
            $baseUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
            
            // Normalise path with rtrim and ltrim to avoid double slashes
            $imagePath = rtrim(self::BASE_MEDIA_PATH, '/') . '/' . ltrim($partner->getLogo(), '/');
            
            // Return full URL to the image
            return $baseUrl . $imagePath;
        } catch (\Exception $e) {
            $this->logger->error('Error generating logo URL for partner ' . $partner->getId() . ': ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get fallback logo URL when partner logo is missing
     *
     * @return string|null
     */
    public function getFallbackLogoUrl(): ?string
    {
        try {
            $baseUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
            return $baseUrl . self::BASE_MEDIA_PATH . '/placeholder.png';
        } catch (\Exception $e) {
            $this->logger->error('Error generating fallback logo URL: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get media base URL
     *
     * @return string
     */
    public function getMediaBaseUrl(): string
    {
        try {
            return $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) . self::BASE_MEDIA_PATH;
        } catch (\Exception $e) {
            $this->logger->error('Error generating media base URL: ' . $e->getMessage());
            return '';
        }
    }
}