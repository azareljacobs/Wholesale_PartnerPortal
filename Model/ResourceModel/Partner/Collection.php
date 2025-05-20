<?php
declare(strict_types=1);
/**
 * Partner collection
 *
 * @category  Wholesale
 * @package   Wholesale_PartnerPortal
 */

namespace Wholesale\PartnerPortal\Model\ResourceModel\Partner;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Wholesale\PartnerPortal\Model\Partner;
use Wholesale\PartnerPortal\Model\ResourceModel\Partner as PartnerResource;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'partner_id';

    /**
     * @var string
     */
    protected $_eventPrefix = 'wholesale_partner_collection';

    /**
     * @var string
     */
    protected $_eventObject = 'partner_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            Partner::class,
            PartnerResource::class
        );
    }
}