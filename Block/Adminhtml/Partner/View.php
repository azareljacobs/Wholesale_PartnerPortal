<?php
declare(strict_types=1);
/**
 * Admin partner view block
 *
 * @category  Wholesale
 * @package   Wholesale_PartnerPortal
 */

namespace Wholesale\PartnerPortal\Block\Adminhtml\Partner;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Button;
use Magento\Framework\View\Element\Template;
use Psr\Log\LoggerInterface;
use Wholesale\PartnerPortal\Api\Data\PartnerInterface;
use Wholesale\PartnerPortal\Model\Service\PartnerMediaUrlService;
use Wholesale\PartnerPortal\ViewModel\Adminhtml\PartnerView;

class View extends Template
{
    /**
     * @var LoggerInterface
     */
    private $logger;
    
    /**
     * @var PartnerMediaUrlService
     */
    private $mediaUrlService;

    /**
     * @param Context $context
     * @param LoggerInterface $logger
     * @param PartnerMediaUrlService $mediaUrlService
     * @param array $data
     */
    public function __construct(
        Context $context,
        LoggerInterface $logger,
        PartnerMediaUrlService $mediaUrlService,
        array $data = []
    ) {
        $this->logger = $logger;
        $this->mediaUrlService = $mediaUrlService;
        parent::__construct($context, $data);
        // ViewModel is expected to be injected via layout XML
    }

    /**
     * Get current partner
     *
     * @return PartnerInterface|null
     */
    public function getPartner(): ?PartnerInterface
    {
        $viewModel = $this->getViewModel();
        if ($viewModel instanceof PartnerView) {
            return $viewModel->getPartner();
        }
        $this->logger->warning('PartnerView ViewModel not found or not of expected type in Block.');
        return null;
    }

    /**
     * Get the ViewModel instance.
     *
     * @return PartnerView|null
     */
    private function getViewModel(): ?PartnerView
    {
        // The 'view_model' key matches the argument name in the layout XML
        return $this->getData('view_model');
    }

    /**
     * Check if partner exists
     *
     * @return bool
     */
    public function hasPartner(): bool
    {
        return $this->getPartner() !== null;
    }

    /**
     * Get partner ID
     *
     * @return int|null
     */
    public function getPartnerId(): ?int
    {
        $partner = $this->getPartner();
        return $partner ? $partner->getId() : null;
    }

    /**
     * Get partner logo URL
     *
     * @return string|null
     */
    public function getLogoUrl(): ?string
    {
        return $this->mediaUrlService->getLogoUrl($this->getPartner());
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

    /**
     * Get back URL
     *
     * @return string
     */
    public function getBackUrl(): string
    {
        return $this->getUrl('*/*/');
    }

    /**
     * Get edit URL
     *
     * @return string
     */
    public function getEditUrl(): string
    {
        return $this->getUrl('*/*/edit', ['partner_id' => $this->getPartnerId()]);
    }
    
    /**
     * Get URL parameters for edit action
     *
     * @return array
     */
    public function getButtonUrlParams(): array
    {
        return ['partner_id' => $this->getPartnerId()];
    }
    
    /**
     * Get frontend URL for the partner
     *
     * @return string|null
     */
    public function getFrontendUrl(): ?string
    {
        $partner = $this->getPartner();
        if (!$partner || !$partner->getSlug()) {
            return null;
        }
        
        // Build the frontend URL using the partner's slug
        return $this->_storeManager->getStore()->getBaseUrl() . 'wholesale/partners/view/slug/' . $partner->getSlug();
    }
}