<?php
declare(strict_types=1);
/**
 * Partner search results model
 *
 * @category  Wholesale
 * @package   Wholesale_PartnerPortal
 */

namespace Wholesale\PartnerPortal\Model;

use Magento\Framework\Api\SearchResults;
use Wholesale\PartnerPortal\Api\Data\PartnerSearchResultsInterface;

/**
 * Class PartnerSearchResults
 * 
 * Implementation of PartnerSearchResultsInterface that extends Magento's SearchResults
 */
class PartnerSearchResults extends SearchResults implements PartnerSearchResultsInterface
{
}