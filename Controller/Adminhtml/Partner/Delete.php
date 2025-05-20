<?php
declare(strict_types=1);
/**
 * Delete partner controller
 *
 * @category  Wholesale
 * @package   Wholesale_PartnerPortal
 */

namespace Wholesale\PartnerPortal\Controller\Adminhtml\Partner;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Wholesale\PartnerPortal\Model\Service\PartnerCommandService;

class Delete extends Action
{
    /**
     * Authorization level
     */
    const ADMIN_RESOURCE = 'Wholesale_PartnerPortal::partner_delete';

    /**
     * @var PartnerCommandService
     */
    protected $commandService;

    /**
     * @param Context $context
     * @param PartnerCommandService $commandService
     */
    public function __construct(
        Context $context,
        PartnerCommandService $commandService
    ) {
        parent::__construct($context);
        $this->commandService = $commandService;
    }

    /**
     * Delete action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute(): \Magento\Framework\Controller\ResultInterface
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $id = (int)$this->getRequest()->getParam('partner_id');
        
        if ($id) {
            try {
                // Delegate partner deletion to command service
                $this->commandService->delete($id);
                
                // Display success message
                $this->messageManager->addSuccessMessage(__('You deleted the partner.'));
                return $resultRedirect->setPath('*/*/');
            } catch (NoSuchEntityException $e) {
                // Display not found message
                $this->messageManager->addErrorMessage(__('We can\'t find a partner to delete.'));
            } catch (Exception $e) {
                // Display generic message to prevent information leakage
                $this->messageManager->addErrorMessage(__('An error occurred while deleting the partner.'));
                return $resultRedirect->setPath('*/*/edit', ['partner_id' => $id]);
            }
        } else {
            $this->messageManager->addErrorMessage(__('We can\'t find a partner to delete.'));
        }
        
        return $resultRedirect->setPath('*/*/');
    }
}