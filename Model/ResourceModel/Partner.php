<?php
declare(strict_types=1);
/**
 * Partner resource model
 *
 * @category  Wholesale
 * @package   Wholesale_PartnerPortal
 */

namespace Wholesale\PartnerPortal\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;
use Psr\Log\LoggerInterface;

class Partner extends AbstractDb
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Constructor
     *
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param LoggerInterface $logger
     * @param string|null $connectionName
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        LoggerInterface $logger,
        $connectionName = null
    ) {
        $this->logger = $logger;
        parent::__construct($context, $connectionName);
    }
    /**
     * Initialise resource model
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init('wholesale_partner', 'partner_id');
    }
    
    /**
     * Check for unique slug before save
     *
     * @param AbstractModel $object
     * @return $this
     * @throws AlreadyExistsException
     * @throws LocalizedException
     */
    protected function _beforeSave(AbstractModel $object): self
    {
        if ($object->isObjectNew() || $object->dataHasChangedFor('slug')) {
            try {
                if ($this->isSlugExists($object->getSlug(), $object->getId())) {
                    throw new AlreadyExistsException(
                        __('A partner with the same URL Key already exists. Please choose a unique URL Key.')
                    );
                }
            } catch (LocalizedException $e) {
                throw $e;
            } catch (\Exception $e) {
                $this->logger->error(
                    'Error checking slug before save: ' . $e->getMessage(),
                    ['slug' => $object->getSlug(), 'partner_id' => $object->getId()]
                );
                throw new LocalizedException(
                    __('An error occurred while saving the partner. Please try again.')
                );
            }
        }
        
        return parent::_beforeSave($object);
    }
    
    /**
     * Check if a partner with the given slug already exists
     *
     * @param string $slug The URL key to check
     * @param int|null $excludeId Optional partner ID to exclude from the check
     * @return bool True if the slug exists for another partner, false otherwise
     * @throws LocalizedException If there is a database error during validation
     */
    private function isSlugExists(string $slug, ?int $excludeId = null): bool
    {
        try {
            $connection = $this->getConnection();
            $select = $connection->select()
                ->from($this->getMainTable(), 'partner_id')
                ->where('slug = ?', $slug);
                
            if ($excludeId) {
                $select->where('partner_id <> ?', $excludeId);
            }
            
            $result = $connection->fetchOne($select);
            return (bool)$result;
        } catch (\Exception $e) {
            $this->logger->error(
                'Error while checking slug existence: ' . $e->getMessage(),
                ['slug' => $slug, 'exclude_id' => $excludeId]
            );
            throw new LocalizedException(
                __('An error occurred while validating the URL Key. Please try again.')
            );
        }
    }
}