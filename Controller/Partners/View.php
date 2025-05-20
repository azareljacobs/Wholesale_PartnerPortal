<?php
declare(strict_types=1);

namespace Wholesale\PartnerPortal\Controller\Partners;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Registry;
use Wholesale\PartnerPortal\Model\Service\PartnerQueryService;

class View implements HttpGetActionInterface
{
    /**
     * @var PageFactory
     */
    private $resultPageFactory;

    /**
     * @var RedirectFactory
     */
    private $resultRedirectFactory;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var PartnerQueryService
     */
    private $queryService;

    /**
     * @var Registry
     */
    private $coreRegistry;

    /**
     * @param PageFactory $resultPageFactory
     * @param RedirectFactory $resultRedirectFactory
     * @param RequestInterface $request
     * @param PartnerQueryService $queryService
     * @param Registry $coreRegistry
     */
    public function __construct(
        PageFactory $resultPageFactory,
        RedirectFactory $resultRedirectFactory,
        RequestInterface $request,
        PartnerQueryService $queryService,
        Registry $coreRegistry
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->request = $request;
        $this->queryService = $queryService;
        $this->coreRegistry = $coreRegistry;
    }

    /**
     * Partner view page
     *
     * @return \Magento\Framework\View\Result\Page|\Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        // Get slug from standard URL parameter
        $slug = $this->request->getParam('slug');
        
        if (!$slug) {
            // No slug found, redirect to partners list
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath('wholesale/partners/index');
        }
        
        try {
            // Get partner by slug and automatically validate it's active
            $partner = $this->queryService->getBySlug($slug, true);
            
            // Register partner for blocks to use
            $this->coreRegistry->register('current_partner', $partner);
            
            // Create and configure result page
            $resultPage = $this->resultPageFactory->create();
            $resultPage->getConfig()->getTitle()->set($partner->getName());
            
            return $resultPage;
        } catch (NoSuchEntityException $e) {
            // Partner not found or not active, redirect to 404 page
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath('cms/noroute/index');
        }
    }
}