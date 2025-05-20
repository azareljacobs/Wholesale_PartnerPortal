<?php
declare(strict_types=1);
/**
 * Logo column UI component
 *
 * @category  Wholesale
 * @package   Wholesale_PartnerPortal
 */

namespace Wholesale\PartnerPortal\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\Component\Listing\Columns\Column;

class Logo extends Column
{
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param StoreManagerInterface $storeManager
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        StoreManagerInterface $storeManager,
        array $components = [],
        array $data = []
    ) {
        $this->storeManager = $storeManager;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            $fieldName = $this->getData('name');
            $baseUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
            
            // Get base path for consistency with other components
            $basePath = 'wholesale/partner';
            
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item[$fieldName]) && $item[$fieldName]) {
                    // Normalise path with rtrim and ltrim to avoid double slashes
                    $imagePath = rtrim($basePath, '/') . '/' . ltrim($item[$fieldName], '/');
                    $item[$fieldName . '_src'] = $baseUrl . $imagePath;
                    $item[$fieldName . '_alt'] = $item['name'] ?? 'Partner Logo';
                    $item[$fieldName . '_orig_src'] = $baseUrl . $imagePath;
                }
            }
        }

        return $dataSource;
    }
}