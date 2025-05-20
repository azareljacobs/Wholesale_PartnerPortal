<?php
/**
 * Partner search results interface
 *
 * @category  Wholesale
 * @package   Wholesale_PartnerPortal
 */

namespace Wholesale\PartnerPortal\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

/**
 * Interface PartnerSearchResultsInterface
 * @api
 */
interface PartnerSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Get partners list
     *
     * @return \Wholesale\PartnerPortal\Api\Data\PartnerInterface[]
     */
    public function getItems();

    /**
     * Set partners list
     *
     * @param \Wholesale\PartnerPortal\Api\Data\PartnerInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}