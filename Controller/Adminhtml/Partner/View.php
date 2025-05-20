<?php
/**
 * View partner controller for admin
 *
 * @category  Wholesale
 * @package   Wholesale_PartnerPortal
 */

namespace Wholesale\PartnerPortal\Controller\Adminhtml\Partner;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Wholesale\PartnerPortal\Api\PartnerRepositoryInterface;
use Wholesale\PartnerPortal\Model\PartnerFactory;

class View extends Action
{
    /**
     * Authorization level
     */
    const ADMIN_RESOURCE = 'Wholesale_PartnerPortal::partner';

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var PartnerRepositoryInterface
     */
    protected $partnerRepository;

    /**
     * @var Registry
     */
    protected $coreRegistry;

    /**
     * @var PartnerFactory
     */
    protected $partnerFactory;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param PartnerRepositoryInterface $partnerRepository
     * @param Registry $coreRegistry
     * @param PartnerFactory $partnerFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        PartnerRepositoryInterface $partnerRepository,
        Registry $coreRegistry,
        PartnerFactory $partnerFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->partnerRepository = $partnerRepository;
        $this->coreRegistry = $coreRegistry;
        $this->partnerFactory = $partnerFactory;
    }

    /**
     * View partner in admin
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('partner_id');
        
        if (!$id) {
            $this->messageManager->addErrorMessage(__('Partner ID is required.'));
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath('*/*/');
        }
        
        try {
            $model = $this->partnerRepository->getById($id);
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__('This partner no longer exists.'));
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath('*/*/');
        }

        // Register the partner model for use in blocks
        $this->coreRegistry->register('current_partner', $model);

        // Create and configure the result page
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Wholesale_PartnerPortal::partner');
        $resultPage->addBreadcrumb(__('Partners'), __('Partners'));
        $resultPage->addBreadcrumb(__('View Partner'), __('View Partner'));
        $resultPage->getConfig()->getTitle()->prepend(__('View Partner: %1', $model->getName()));

        return $resultPage;
    }
}