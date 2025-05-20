<?php
/**
 * Partner form data provider
 *
 * @category  Wholesale
 * @package   Wholesale_PartnerPortal
 */

namespace Wholesale\PartnerPortal\Model\Partner;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Ui\DataProvider\Modifier\PoolInterface;
use Wholesale\PartnerPortal\Model\ResourceModel\Partner\CollectionFactory;

class DataProvider extends AbstractDataProvider
{
    /**
     * @var CollectionFactory
     */
    protected $collection;

    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;

    /**
     * @var array
     */
    protected $loadedData;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;
    
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
     * @param StoreManagerInterface $storeManager
     * @param array $meta
     * @param array $data
     * @param PoolInterface|null $pool
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        DataPersistorInterface $dataPersistor,
        StoreManagerInterface $storeManager,
        RequestInterface $request,
        array $meta = [],
        array $data = [],
        PoolInterface $pool = null
    ) {
        $this->collection = $collectionFactory->create();
        $this->dataPersistor = $dataPersistor;
        $this->storeManager = $storeManager;
        $this->request = $request;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }

        // Filter the collection by partner_id if it's in the request
        $partnerId = $this->getPartnerId();
        if ($partnerId) {
            $this->collection->addFieldToFilter('partner_id', $partnerId);
        }
        
        $items = $this->collection->getItems();
        
        foreach ($items as $partner) {
            $this->loadedData[$partner->getId()] = $partner->getData();
            
            if ($partner->getLogo()) {
                $logoUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA)
                    . 'wholesale/partner/' . $partner->getLogo();
                
                // Prepare the logo data array
                $logoData = [
                    'name' => $partner->getLogo(),
                    'url' => $logoUrl
                ];
                
                // Only perform file operations if the file exists
                $logoPath = $this->getLogoPath($partner->getLogo());
                if (file_exists($logoPath)) {
                    $logoData['size'] = filesize($logoPath);
                    $logoData['type'] = $this->getMimeType($partner->getLogo());
                }
                
                $this->loadedData[$partner->getId()]['logo'] = [$logoData];
            }
        }

        $data = $this->dataPersistor->get('wholesale_partner');
        if (!empty($data)) {
            $partner = $this->collection->getNewEmptyItem();
            $partner->setData($data);
            
            // Use a specific key for new items rather than null
            $partnerKey = $partner->getId() ? $partner->getId() : 'new';
            $this->loadedData[$partnerKey] = $partner->getData();
            
            // Handle logo data for persisted data
            if (isset($data['logo']) && is_array($data['logo'])) {
                $this->loadedData[$partnerKey]['logo'] = $data['logo'];
            }
            
            $this->dataPersistor->clear('wholesale_partner');
        }

        return $this->loadedData;
    }

    /**
     * Get logo absolute path
     *
     * @param string $logo
     * @return string
     */
    /**
     * Get partner ID from request or null if creating a new partner
     *
     * @return int|null
     */
    private function getPartnerId()
    {
        return $this->request ? (int)$this->request->getParam($this->requestFieldName) : null;
    }

    /**
     * Get logo absolute path
     *
     * @param string $logo
     * @return string
     */
    private function getLogoPath($logo)
    {
        return BP . '/pub/media/wholesale/partner/' . $logo;
    }

    /**
     * Get MIME type of file
     *
     * @param string $file
     * @return string
     */
    private function getMimeType($file)
    {
        $extension = pathinfo($file, PATHINFO_EXTENSION);
        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif'
        ];
        
        return isset($mimeTypes[$extension]) ? $mimeTypes[$extension] : 'application/octet-stream';
    }
}