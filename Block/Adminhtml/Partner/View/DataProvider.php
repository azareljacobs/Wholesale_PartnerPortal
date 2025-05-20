<?php
declare(strict_types=1);
/**
 * Partner View Data Provider
 *
 * @category  Wholesale
 * @package   Wholesale_PartnerPortal
 */

namespace Wholesale\PartnerPortal\Block\Adminhtml\Partner\View;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Ui\DataProvider\Modifier\ModifierInterface;
use Magento\Ui\DataProvider\Modifier\PoolInterface;
use Wholesale\PartnerPortal\Model\ResourceModel\Partner\CollectionFactory;
use Wholesale\PartnerPortal\Api\PartnerRepositoryInterface;
use Magento\Framework\App\RequestInterface;

class DataProvider extends AbstractDataProvider
{
    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;

    /**
     * @var array
     */
    protected $loadedData;

    /**
     * @var PoolInterface
     */
    protected $pool;

    /**
     * @var PartnerRepositoryInterface
     */
    protected $partnerRepository;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param DataPersistorInterface $dataPersistor
     * @param PartnerRepositoryInterface $partnerRepository
     * @param RequestInterface $request
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        DataPersistorInterface $dataPersistor,
        PartnerRepositoryInterface $partnerRepository,
        RequestInterface $request,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection = $collectionFactory->create();
        $this->dataPersistor = $dataPersistor;
        $this->partnerRepository = $partnerRepository;
        $this->request = $request;
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData(): array
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }
        
        $partnerId = $this->request->getParam('partner_id');
        if ($partnerId) {
            try {
                $partner = $this->partnerRepository->getById((int)$partnerId);
                $this->loadedData[$partnerId] = $partner->getData();
                
                // Format logo data for display
                if (!empty($this->loadedData[$partnerId]['logo'])) {
                    $logoName = $this->loadedData[$partnerId]['logo'];
                    unset($this->loadedData[$partnerId]['logo']);
                    $this->loadedData[$partnerId]['logo'][0] = [
                        'name' => $logoName,
                        'url' => $partner->getLogoUrl(),
                        'size' => 0,
                        'type' => ''
                    ];
                }
            } catch (\Exception $e) {
                // Partner not found
            }
        }

        return $this->loadedData;
    }
}