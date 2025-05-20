<?php
declare(strict_types=1);
/**
 * Edit partner controller
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

class Edit extends Action
{
    /**
     * Authorization level
     */
    const ADMIN_RESOURCE = 'Wholesale_PartnerPortal::partner_save';

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
     * Edit partner
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute(): \Magento\Framework\Controller\ResultInterface
    {
        $id = $this->getRequest()->getParam('partner_id');
        $model = $this->partnerFactory->create();

        if ($id) {
            try {
                $model = $this->partnerRepository->getById((int)$id);
            } catch (NoSuchEntityException $e) {
                $this->messageManager->addErrorMessage(__('This partner no longer exists.'));
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('*/*/');
            }
        }

        $this->coreRegistry->register('wholesale_partner', $model);

        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Wholesale_PartnerPortal::partner');
        $resultPage->addBreadcrumb(__('Partners'), __('Partners'));
        $resultPage->addBreadcrumb(
            $id ? __('Edit Partner') : __('New Partner'),
            $id ? __('Edit Partner') : __('New Partner')
        );
        $resultPage->getConfig()->getTitle()->prepend(__('Partners'));
        $resultPage->getConfig()->getTitle()->prepend(
            $model->getId() ? $model->getName() : __('New Partner')
        );

        return $resultPage;
    }
}