<?php
/**
 * Admin partner view block
 *
 * @category  Wholesale
 * @package   Wholesale_PartnerPortal
 */

namespace Wholesale\PartnerPortal\Block\Adminhtml\Partner;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Button;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Psr\Log\LoggerInterface;
use Wholesale\PartnerPortal\Api\Data\PartnerInterface;
use Wholesale\PartnerPortal\Model\Service\PartnerMediaUrlService;

class View extends Template
{
    /**
     * @var Registry
     */
    private $coreRegistry;

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
     * @param Registry $coreRegistry
     * @param LoggerInterface $logger
     * @param PartnerMediaUrlService $mediaUrlService
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        LoggerInterface $logger,
        PartnerMediaUrlService $mediaUrlService,
        array $data = []
    ) {
        $this->coreRegistry = $coreRegistry;
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
        return $this->coreRegistry->registry('current_partner');
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
     * Get partner ID
     *
     * @return int|null
     */
    public function getPartnerId()
    {
        $partner = $this->getPartner();
        return $partner ? $partner->getId() : null;
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
     * Get back URL
     *
     * @return string
     */
    public function getBackUrl()
    {
        return $this->getUrl('*/*/');
    }

    /**
     * Get edit URL
     *
     * @return string
     */
    public function getEditUrl()
    {
        return $this->getUrl('*/*/edit', ['partner_id' => $this->getPartnerId()]);
    }
    
    /**
     * Get URL parameters for edit action
     *
     * @return array
     */
    public function getButtonUrlParams()
    {
        return ['partner_id' => $this->getPartnerId()];
    }
}