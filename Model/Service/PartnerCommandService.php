<?php
declare(strict_types=1);
/**
 * Partner command service
 *
 * @category  Wholesale
 * @package   Wholesale_PartnerPortal
 */

namespace Wholesale\PartnerPortal\Model\Service;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;
use Wholesale\PartnerPortal\Api\Data\PartnerInterface;
use Wholesale\PartnerPortal\Api\Data\PartnerInterfaceFactory;
use Wholesale\PartnerPortal\Api\PartnerRepositoryInterface;

/**
 * Service class to handle partner commands (create, update, delete)
 * Following Command/Query Separation Pattern
 */
class PartnerCommandService
{
    /**
     * @var PartnerRepositoryInterface
     */
    private $partnerRepository;

    /**
     * @var PartnerInterfaceFactory
     */
    private $partnerFactory;

    /**
     * @var PartnerDataSanitizerService
     */
    private $dataSanitizerService;

    /**
     * @var PartnerLogoService
     */
    private $logoService;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param PartnerRepositoryInterface $partnerRepository
     * @param PartnerInterfaceFactory $partnerFactory
     * @param PartnerDataSanitizerService $dataSanitizerService
     * @param PartnerLogoService $logoService
     * @param LoggerInterface $logger
     */
    public function __construct(
        PartnerRepositoryInterface $partnerRepository,
        PartnerInterfaceFactory $partnerFactory,
        PartnerDataSanitizerService $dataSanitizerService,
        PartnerLogoService $logoService,
        LoggerInterface $logger
    ) {
        $this->partnerRepository = $partnerRepository;
        $this->partnerFactory = $partnerFactory;
        $this->dataSanitizerService = $dataSanitizerService;
        $this->logoService = $logoService;
        $this->logger = $logger;
    }

    /**
     * Create or update a partner
     *
     * @param array $data Partner data
     * @return PartnerInterface
     * @throws CouldNotSaveException
     */
    public function execute(array $data): PartnerInterface
    {
        // Sanitize input data
        $sanitizedData = $this->dataSanitizerService->sanitize($data);
        
        try {
            // Load existing partner or create new one
            if (!empty($sanitizedData[PartnerInterface::PARTNER_ID])) {
                $partner = $this->partnerRepository->getById((int)$sanitizedData[PartnerInterface::PARTNER_ID]);
            } else {
                $partner = $this->partnerFactory->create();
            }
            
            // Process logo data if present
            if (isset($sanitizedData[PartnerInterface::LOGO]) && is_array($sanitizedData[PartnerInterface::LOGO])) {
                $sanitizedData = $this->processLogoData($sanitizedData);
            }
            
            // Set data to the partner model
            foreach ($sanitizedData as $key => $value) {
                // Skip ID field as it's set directly by the resource model
                if ($key !== PartnerInterface::PARTNER_ID) {
                    $partner->setData($key, $value);
                }
            }
            
            // Save the partner
            $this->partnerRepository->save($partner);
            
            // Move logo from temporary to permanent storage if needed
            if (isset($sanitizedData[PartnerInterface::LOGO]) && !is_array($sanitizedData[PartnerInterface::LOGO])) {
                $this->logoService->moveLogoToPermanentStorage($sanitizedData[PartnerInterface::LOGO], $partner->getId());
            }
            
            return $partner;
        } catch (\Exception $e) {
            $this->logger->error('Error saving partner: ' . $e->getMessage(), ['exception' => $e]);
            throw new CouldNotSaveException(__('Error saving partner: %1', $e->getMessage()));
        }
    }

    /**
     * Process the logo data from form submission
     *
     * @param array $data
     * @return array
     */
    private function processLogoData(array $data): array
    {
        return $this->dataSanitizerService->processLogoData($data);
    }

    /**
     * Delete a partner
     *
     * @param int $partnerId
     * @return bool
     * @throws NoSuchEntityException
     */
    public function delete(int $partnerId): bool
    {
        try {
            $partner = $this->partnerRepository->getById($partnerId);
            
            // Delete the logo file if it exists
            if ($partner->getLogo()) {
                $this->logoService->deleteLogoFile($partner->getLogo());
            }
            
            // Delete the partner
            return $this->partnerRepository->delete($partner);
        } catch (NoSuchEntityException $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->logger->error('Error deleting partner: ' . $e->getMessage(), ['exception' => $e]);
            throw new \Magento\Framework\Exception\CouldNotDeleteException(
                __('Error deleting partner: %1', $e->getMessage())
            );
        }
    }
}