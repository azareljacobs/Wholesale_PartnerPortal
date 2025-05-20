<?php
/**
 * Validate partner URL key controller
 *
 * @category  Wholesale
 * @package   Wholesale_PartnerPortal
 */

namespace Wholesale\PartnerPortal\Controller\Adminhtml\Partner;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Psr\Log\LoggerInterface;
use Wholesale\PartnerPortal\Model\Service\PartnerQueryService;

class Validate extends Action
{
    /**
     * Authorization level
     */
    const ADMIN_RESOURCE = 'Wholesale_PartnerPortal::partner_save';

    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var PartnerQueryService
     */
    protected $queryService;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param PartnerQueryService $queryService
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        PartnerQueryService $queryService,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->queryService = $queryService;
        $this->logger = $logger;
    }

    /**
     * Validate URL key (slug) uniqueness
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        $response = [
            'valid' => true,
            'message' => null
        ];

        try {
            $slug = $this->getRequest()->getParam('slug');
            $partnerId = (int)$this->getRequest()->getParam('partner_id');
            
            if (!$slug) {
                $response = [
                    'valid' => false,
                    'message' => __('Please enter a valid URL Key.')
                ];
                return $resultJson->setData($response);
            }
            
            // Validate slug format (same regex as client-side)
            if (!preg_match('/^[a-z0-9-]+$/', $slug)) {
                $response = [
                    'valid' => false,
                    'message' => __('URL Key can only contain lowercase letters, numbers, and hyphens.')
                ];
                return $resultJson->setData($response);
            }
            
            // Check if slug exists using the query service
            $exists = $this->queryService->exists($slug, 'slug', false);
            
            // If slug exists but it's for the current partner being edited, it's still valid
            if ($exists && $partnerId > 0) {
                try {
                    $partner = $this->queryService->getBySlug($slug);
                    if ($partner->getId() == $partnerId) {
                        $exists = false; // It's the same partner, so the slug is valid
                    }
                } catch (\Exception $e) {
                    $this->logger->error('Error checking partner by slug: ' . $e->getMessage());
                }
            }
            
            if ($exists) {
                $response = [
                    'valid' => false,
                    'message' => __('A partner with the same URL Key already exists. Please choose a unique URL Key.')
                ];
            }
        } catch (\Exception $e) {
            $this->logger->error('Error validating slug: ' . $e->getMessage());
            $response = [
                'valid' => false,
                'message' => __('An error occurred while validating the URL Key.')
            ];
        }

        return $resultJson->setData($response);
    }
}