<?php
declare(strict_types=1);
/**
 * Generic button block for view page
 *
 * @category  Wholesale
 * @package   Wholesale_PartnerPortal
 */

namespace Wholesale\PartnerPortal\Block\Adminhtml\Partner\View;

use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Wholesale\PartnerPortal\Api\PartnerRepositoryInterface;

class GenericButton
{
    /**
     * @var Context
     */
    protected $context;

    /**
     * @var PartnerRepositoryInterface
     */
    protected $partnerRepository;

    /**
     * @param Context $context
     * @param PartnerRepositoryInterface $partnerRepository
     */
    public function __construct(
        Context $context,
        PartnerRepositoryInterface $partnerRepository
    ) {
        $this->context = $context;
        $this->partnerRepository = $partnerRepository;
    }

    /**
     * Return partner ID
     *
     * @return int|null
     */
    public function getPartnerId(): ?int
    {
        try {
            $partnerId = $this->context->getRequest()->getParam('partner_id');
            if ($partnerId) {
                $this->partnerRepository->getById((int)$partnerId);
                return (int)$partnerId;
            }
        } catch (NoSuchEntityException $e) {
            return null;
        }
        return null;
    }

    /**
     * Generate url by route and parameters
     *
     * @param string $route
     * @param array $params
     * @return string
     */
    public function getUrl(string $route = '', array $params = []): string
    {
        return $this->context->getUrlBuilder()->getUrl($route, $params);
    }
}