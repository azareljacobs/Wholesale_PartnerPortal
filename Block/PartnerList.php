<?php
declare(strict_types=1);

namespace Wholesale\PartnerPortal\Block;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Psr\Log\LoggerInterface;
use Wholesale\PartnerPortal\Api\Data\PartnerInterface;
use Wholesale\PartnerPortal\Api\PartnerRepositoryInterface;
use Wholesale\PartnerPortal\Model\Partner;
use Wholesale\PartnerPortal\Model\ResourceModel\Partner\CollectionFactory;
use Wholesale\PartnerPortal\Model\Service\PartnerMediaUrlService;

class PartnerList extends Template implements IdentityInterface
{
    /**
     * Default number of partners per page
     */
    const DEFAULT_PARTNERS_PER_PAGE = 12;
    
    /**
     * @var PartnerRepositoryInterface
     */
    private $partnerRepository;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;
    
    /**
     * @var \Wholesale\PartnerPortal\Model\ResourceModel\Partner\Collection
     */
    private $partnersCollection = null;

    /**
     * @var array
     */
    private $partners = null;
    
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
     * @param PartnerRepositoryInterface $partnerRepository
     * @param CollectionFactory $collectionFactory
     * @param LoggerInterface $logger
     * @param PartnerMediaUrlService $mediaUrlService
     * @param array $data
     */
    public function __construct(
        Context $context,
        PartnerRepositoryInterface $partnerRepository,
        CollectionFactory $collectionFactory,
        LoggerInterface $logger,
        PartnerMediaUrlService $mediaUrlService,
        array $data = []
    ) {
        $this->partnerRepository = $partnerRepository;
        $this->collectionFactory = $collectionFactory;
        $this->logger = $logger;
        $this->mediaUrlService = $mediaUrlService;
        parent::__construct($context, $data);
    }

    /**
     * Get partner collection for pager
     *
     * @return \Wholesale\PartnerPortal\Model\ResourceModel\Partner\Collection
     */
    public function getPartnersCollection(): \Wholesale\PartnerPortal\Model\ResourceModel\Partner\Collection
    {
        if ($this->partnersCollection === null) {
            $this->partnersCollection = $this->collectionFactory->create();
            $this->partnersCollection->addFieldToFilter(PartnerInterface::IS_ACTIVE, true);
            
            // Set default sort order by name ascending
            $this->partnersCollection->setOrder(PartnerInterface::NAME, 'ASC');
            
            // Configure pagination
            if ($this->getPageSize()) {
                $this->partnersCollection->setPageSize($this->getPageSize());
                $this->partnersCollection->setCurPage($this->getCurrentPage());
            }
        }
        
        return $this->partnersCollection;
    }

    /**
     * Get partner list
     *
     * @return array
     */
    public function getPartners(): array
    {
        if ($this->partners === null) {
            $this->partners = $this->getPartnersCollection()->getItems();
        }
        
        return $this->partners;
    }

    /**
     * Get partner view URL
     *
     * @param \Wholesale\PartnerPortal\Api\Data\PartnerInterface $partner
     * @return string
     */
    public function getPartnerUrl($partner): string
    {
        return $this->getUrl('wholesale/partners/view', ['slug' => $partner->getSlug()]);
    }

    /**
     * Get partner logo URL
     *
     * @param PartnerInterface $partner
     * @return string|null
     */
    public function getLogoUrl($partner): ?string
    {
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
    
    /**
     * Get page size for partner list
     *
     * @return int
     */
    public function getPageSize(): int
    {
        return (int)$this->getData('page_size') ?: self::DEFAULT_PARTNERS_PER_PAGE;
    }
    
    /**
     * Get current page from request or default to 1
     *
     * @return int
     */
    public function getCurrentPage(): int
    {
        return $this->getRequest()->getParam('p') ? (int)$this->getRequest()->getParam('p') : 1;
    }
    
    /**
     * Prepare layout for pagination
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        
        $collection = $this->getPartnersCollection();
        if ($collection) {
            $pager = $this->getLayout()->createBlock(
                \Magento\Theme\Block\Html\Pager::class,
                'wholesale.partner.list.pager'
            );
            
            if ($pager) {
                $pager->setCollection($collection);
                $pager->setAvailableLimit([self::DEFAULT_PARTNERS_PER_PAGE => self::DEFAULT_PARTNERS_PER_PAGE]);
                $pager->setShowPerPage(false);
                $this->setChild('pager', $pager);
            }
        }
        
        return $this;
    }
    
    /**
     * Get pager HTML
     *
     * @return string
     */
    public function getPagerHtml(): string
    {
        return $this->getChildHtml('pager');
    }
    
    /**
     * Get identities for cache invalidation
     *
     * @return string[]
     */
    public function getIdentities(): array
    {
        $identities = [Partner::CACHE_TAG . '_list'];
        
        // Add general partner cache tag
        $identities[] = Partner::CACHE_TAG;
        
        // Add individual partner cache tags
        foreach ($this->getPartners() as $partner) {
            $identities = array_merge($identities, $partner->getIdentities());
        }
        
        return array_unique($identities);
    }
}