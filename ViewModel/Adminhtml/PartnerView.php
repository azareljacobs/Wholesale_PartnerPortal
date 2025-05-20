<?php
declare(strict_types=1);

namespace Wholesale\PartnerPortal\ViewModel\Adminhtml;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Psr\Log\LoggerInterface;
use Wholesale\PartnerPortal\Api\Data\PartnerInterface;
use Wholesale\PartnerPortal\Api\PartnerRepositoryInterface;

class PartnerView implements ArgumentInterface
{
    /**
     * @var PartnerRepositoryInterface
     */
    private $partnerRepository;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var PartnerInterface|null
     */
    private $partner;

    /**
     * @param PartnerRepositoryInterface $partnerRepository
     * @param RequestInterface $request
     * @param LoggerInterface $logger
     */
    public function __construct(
        PartnerRepositoryInterface $partnerRepository,
        RequestInterface $request,
        LoggerInterface $logger
    ) {
        $this->partnerRepository = $partnerRepository;
        $this->request = $request;
        $this->logger = $logger;
    }

    /**
     * Get current partner
     *
     * @return PartnerInterface|null
     */
    public function getPartner(): ?PartnerInterface
    {
        if ($this->partner === null) {
            $partnerId = (int)$this->request->getParam('partner_id');
            if ($partnerId) {
                try {
                    $this->partner = $this->partnerRepository->getById($partnerId);
                } catch (NoSuchEntityException $e) {
                    $this->logger->error(
                        __('Partner with ID "%1" not found in ViewModel: %2', $partnerId, $e->getMessage())
                    );
                    $this->partner = null; // Explicitly set to null
                } catch (\Exception $e) {
                    $this->logger->critical(
                        __('Error loading partner with ID "%1" in ViewModel: %2', $partnerId, $e->getMessage())
                    );
                    $this->partner = null; // Explicitly set to null
                }
            }
        }
        return $this->partner;
    }
}