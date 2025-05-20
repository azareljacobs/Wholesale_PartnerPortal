<?php
/**
 * Delete partner logo controller
 *
 * @category  Wholesale
 * @package   Wholesale_PartnerPortal
 */

namespace Wholesale\PartnerPortal\Controller\Adminhtml\Partner;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Wholesale\PartnerPortal\Api\PartnerRepositoryInterface;
use Wholesale\PartnerPortal\Model\PartnerFactory;
use Wholesale\PartnerPortal\Model\ImageUploader;

class DeleteImage extends Action
{
    /**
     * Authorization level
     */
    const ADMIN_RESOURCE = 'Wholesale_PartnerPortal::partner_save';

    /**
     * @var ImageUploader
     */
    protected $imageUploader;
    
    /**
     * @var PartnerRepositoryInterface
     */
    protected $partnerRepository;
    
    /**
     * @var PartnerFactory
     */
    protected $partnerFactory;

    /**
     * @param Context $context
     * @param ImageUploader $imageUploader
     * @param PartnerRepositoryInterface $partnerRepository
     * @param PartnerFactory $partnerFactory
     */
    public function __construct(
        Context $context,
        ImageUploader $imageUploader,
        PartnerRepositoryInterface $partnerRepository,
        PartnerFactory $partnerFactory
    ) {
        parent::__construct($context);
        $this->imageUploader = $imageUploader;
        $this->partnerRepository = $partnerRepository;
        $this->partnerFactory = $partnerFactory;
    }

    /**
     * Delete image controller action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $filename = $this->getRequest()->getParam('filename');
        $partnerId = $this->getRequest()->getParam('partner_id');
        $result = ['success' => false];

        if ($filename) {
            try {
                // First, delete the physical file
                $this->imageUploader->deleteFile($filename);
                
                // Next, update the database record if partner_id is provided
                if ($partnerId) {
                    $partner = $this->partnerRepository->getById((int)$partnerId);
                    // Only update if the current logo matches what we're deleting
                    if ($partner->getLogo() === $filename) {
                        $partner->setLogo(''); // Clear the logo field
                        $this->partnerRepository->save($partner);
                    }
                }
                
                $result = [
                    'success' => true,
                    'message' => __('Image successfully deleted.')
                ];
            } catch (Exception $e) {
                $result = [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'errorcode' => $e->getCode()
                ];
            }
        }
        
        return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData($result);
    }
}