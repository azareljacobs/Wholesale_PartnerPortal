<?php
declare(strict_types=1);
/**
 * Save partner controller
 *
 * @category  Wholesale
 * @package   Wholesale_PartnerPortal
 */

namespace Wholesale\PartnerPortal\Controller\Adminhtml\Partner;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\AlreadyExistsException;
use Wholesale\PartnerPortal\Api\PartnerRepositoryInterface;
use Wholesale\PartnerPortal\Model\PartnerFactory;
use Wholesale\PartnerPortal\Model\Service\PartnerDataSanitizerService;
use Wholesale\PartnerPortal\Model\Service\PartnerLogoService;

class Save extends Action
{
    /**
     * Authorization level
     */
    const ADMIN_RESOURCE = 'Wholesale_PartnerPortal::partner_save';

    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;

    /**
     * @var PartnerFactory
     */
    protected $partnerFactory;

    /**
     * @var PartnerRepositoryInterface
     */
    protected $partnerRepository;

    /**
     * @var PartnerLogoService
     */
    protected $logoService;
    
    /**
     * @var PartnerDataSanitizerService
     */
    protected $dataSanitizer;
    
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;
    
    /**
     * @param Context $context
     * @param DataPersistorInterface $dataPersistor
     * @param PartnerFactory $partnerFactory
     * @param PartnerRepositoryInterface $partnerRepository
     * @param PartnerLogoService $logoService
     * @param PartnerDataSanitizerService $dataSanitizer
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        DataPersistorInterface $dataPersistor,
        PartnerFactory $partnerFactory,
        PartnerRepositoryInterface $partnerRepository,
        PartnerLogoService $logoService,
        PartnerDataSanitizerService $dataSanitizer,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->dataPersistor = $dataPersistor;
        $this->partnerFactory = $partnerFactory;
        $this->partnerRepository = $partnerRepository;
        $this->logoService = $logoService;
        $this->dataSanitizer = $dataSanitizer;
        $this->logger = $logger;
    }


    /**
     * Save action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute(): \Magento\Framework\Controller\ResultInterface
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $data = $this->getRequest()->getPostValue();
        
        if ($data) {
            if (empty($data['partner_id'])) {
                $data['partner_id'] = null;
            }

            $model = $this->partnerFactory->create();

            $id = $this->getRequest()->getParam('partner_id');
            if ($id) {
                try {
                    $model = $this->partnerRepository->getById((int)$id);
                } catch (LocalizedException $e) {
                    $this->messageManager->addErrorMessage(__('This partner no longer exists.'));
                    return $resultRedirect->setPath('*/*/');
                }
            }

            // Process logo data using service
            $data = $this->dataSanitizer->processLogoData($data);

            // Sanitize input data before setting it on the model
            $sanitizedData = $this->dataSanitizer->sanitize($data);
            $model->setData($sanitizedData);

            try {
                $this->partnerRepository->save($model);
                
                // Move uploaded logo from tmp to permanent directory using service
                if (isset($data['logo']) && is_string($data['logo'])) {
                    try {
                        $result = $this->logoService->moveLogoToPermanentStorage($data['logo'], $model->getId());
                        
                        if ($result === false) {
                            // File operation failed, but partner was saved
                            $this->messageManager->addWarningMessage(
                                __('The partner was saved, but the logo could not be moved to the permanent directory.')
                            );
                        } else {
                            // Update the partner with the normalized filename
                            $model->setLogo($result);
                            $this->partnerRepository->save($model);
                        }
                    } catch (Exception $e) {
                        $this->logger->error('Error processing logo: ' . $e->getMessage());
                        $this->messageManager->addWarningMessage(
                            __('The partner was saved, but there was an error processing the logo: %1', $e->getMessage())
                        );
                    }
                } elseif (isset($data['logo']) && is_array($data['logo']) && isset($data['logo']['delete']) && $data['logo']['delete']) {
                    // Handle image deletion
                    $oldLogo = $model->getLogo();
                    if ($oldLogo) {
                        try {
                            $this->logoService->deleteLogoFile($oldLogo);
                            $model->setLogo('');
                            $this->partnerRepository->save($model);
                        } catch (Exception $e) {
                            $this->logger->error('Error deleting logo: ' . $e->getMessage());
                            $this->messageManager->addWarningMessage(
                                __('Could not delete the logo: %1', $e->getMessage())
                            );
                        }
                    }
                }
                
                $this->messageManager->addSuccessMessage(__('You saved the partner.'));
                $this->dataPersistor->clear('wholesale_partner');

                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['partner_id' => $model->getId()]);
                }
                return $resultRedirect->setPath('*/*/');
            } catch (AlreadyExistsException $e) {
                $this->messageManager->addErrorMessage(__('A partner with the same URL Key already exists. Please choose a unique URL Key.'));
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (Exception $e) {
                // Log the actual exception for debugging
                $this->logger->error('Error saving partner: ' . $e->getMessage());
                // Display generic message to prevent information leakage
                $this->messageManager->addErrorMessage(__('Something went wrong while saving the partner.'));
            }

            $this->dataPersistor->set('wholesale_partner', $data);
            return $resultRedirect->setPath('*/*/edit', ['partner_id' => $this->getRequest()->getParam('partner_id')]);
        }
        return $resultRedirect->setPath('*/*/');
    }
}