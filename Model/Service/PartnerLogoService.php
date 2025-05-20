<?php
/**
 * Partner logo service
 *
 * @category  Wholesale
 * @package   Wholesale_PartnerPortal
 */

namespace Wholesale\PartnerPortal\Model\Service;

use Exception;
use Psr\Log\LoggerInterface;
use Wholesale\PartnerPortal\Model\ImageUploader;
use Wholesale\PartnerPortal\Api\Data\PartnerInterface;

/**
 * Service class to handle partner logo operations
 */
class PartnerLogoService
{
    /**
     * @var ImageUploader
     */
    private $imageUploader;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ImageUploader $imageUploader
     * @param LoggerInterface $logger
     */
    public function __construct(
        ImageUploader $imageUploader,
        LoggerInterface $logger
    ) {
        $this->imageUploader = $imageUploader;
        $this->logger = $logger;
    }

    /**
     * Move logo file from temporary directory to permanent storage
     *
     * @param string $logoFilename
     * @param int|null $partnerId
     * @return string|false Returns normalized filename on success, false on failure
     */
   public function moveLogoToPermanentStorage(string $logoFilename, ?int $partnerId = null)
   {
       try {
           $normalizedFilename = $this->imageUploader->moveFileFromTmp($logoFilename);
           return $normalizedFilename;
       } catch (Exception $e) {
           // Log the error for better debugging
           $this->logger->error(
               'Error moving partner logo from tmp to permanent directory. ' .
               'Partner ID: ' . ($partnerId ?: 'new') .
               ', Image: ' . $logoFilename .
               ', Error: ' . $e->getMessage()
           );
           return false;
       }
   }

    /**
     * Get full logo URL for a partner
     *
     * @param PartnerInterface $partner
     * @return string|null
     */
    public function getLogoUrl(PartnerInterface $partner): ?string
    {
        $logoFilename = $partner->getLogo();
        if (!$logoFilename) {
            return null;
        }

        try {
            return $this->imageUploader->getMediaUrl($logoFilename);
        } catch (Exception $e) {
            $this->logger->error(
                'Error getting logo URL. ' .
                'Partner ID: ' . $partner->getId() . 
                ', Image: ' . $logoFilename . 
                ', Error: ' . $e->getMessage()
            );
            return null;
        }
    }

    /**
     * Delete a partner logo file
     *
     * @param string $logoFilename
     * @return bool Success flag
     */
    public function deleteLogoFile(string $logoFilename): bool
    {
        try {
            $this->imageUploader->deleteFile($logoFilename);
            return true;
        } catch (Exception $e) {
            $this->logger->error(
                'Error deleting logo file: ' . $logoFilename . 
                ', Error: ' . $e->getMessage()
            );
            return false;
        }
    }
}