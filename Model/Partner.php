<?php
/**
 * Partner model
 *
 * @category  Wholesale
 * @package   Wholesale_PartnerPortal
 */

namespace Wholesale\PartnerPortal\Model;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractExtensibleModel;
use Wholesale\PartnerPortal\Api\Data\PartnerInterface;
use Wholesale\PartnerPortal\Api\Data\PartnerExtensionInterface;

class Partner extends AbstractExtensibleModel implements IdentityInterface, PartnerInterface
{
    /**
     * Cache tag
     */
    const CACHE_TAG = 'wholesale_partner';

    /**
     * @var string
     */
    protected $_cacheTag = self::CACHE_TAG;

    /**
     * @var string
     */
    protected $_eventPrefix = 'wholesale_partner';

    /**
     * Initialise resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Wholesale\PartnerPortal\Model\ResourceModel\Partner::class);
    }

    /**
     * Get identities
     *
     * @return array
     */
    public function getIdentities()
    {
        $identities = [self::CACHE_TAG];
        
        if ($this->getId()) {
            $identities[] = self::CACHE_TAG . '_' . $this->getId();
        }
        
        return $identities;
    }


    /**
     * Get ID
     *
     * @return int
     */
    public function getId(): ?int
    {
        return $this->getData(self::PARTNER_ID) === null ? null : (int)$this->getData(self::PARTNER_ID);
    }

    /**
     * Set ID
     *
     * @param int $id
     * @return $this
     */
    public function setId($id): PartnerInterface
    {
        return $this->setData(self::PARTNER_ID, $id);
    }

    /**
     * Get name
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->getData(self::NAME);
    }

    /**
     * Set name
     *
     * @param string $name
     * @return $this
     */
    public function setName(string $name): PartnerInterface
    {
        return $this->setData(self::NAME, $name);
    }

    /**
     * Get slug
     *
     * @return string|null
     */
    public function getSlug(): ?string
    {
        return $this->getData(self::SLUG);
    }

    /**
     * Set slug
     *
     * @param string $slug
     * @return $this
     */
    public function setSlug(string $slug): PartnerInterface
    {
        return $this->setData(self::SLUG, $slug);
    }

    /**
     * Get logo
     *
     * @return string|null
     */
    public function getLogo(): ?string
    {
        return $this->getData(self::LOGO);
    }

    /**
     * Set logo
     *
     * @param string|null $logo
     * @return $this
     */
    public function setLogo(?string $logo): PartnerInterface
    {
        return $this->setData(self::LOGO, $logo);
    }

    /**
     * Get description
     *
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->getData(self::DESCRIPTION);
    }

    /**
     * Set description
     *
     * @param string|null $description
     * @return $this
     */
    public function setDescription(?string $description): PartnerInterface
    {
        return $this->setData(self::DESCRIPTION, $description);
    }

    /**
     * Get website
     *
     * @return string|null
     */
    public function getWebsite(): ?string
    {
        return $this->getData(self::WEBSITE);
    }

    /**
     * Set website
     *
     * @param string|null $website
     * @return $this
     */
    public function setWebsite(?string $website): PartnerInterface
    {
        return $this->setData(self::WEBSITE, $website);
    }

    /**
     * Get contact email
     *
     * @return string|null
     */
    public function getContactEmail(): ?string
    {
        return $this->getData(self::CONTACT_EMAIL);
    }

    /**
     * Set contact email
     *
     * @param string|null $contactEmail
     * @return $this
     */
    public function setContactEmail(?string $contactEmail): PartnerInterface
    {
        return $this->setData(self::CONTACT_EMAIL, $contactEmail);
    }

    /**
     * Get is_active
     *
     * @return bool|null
     */
    public function getIsActive(): ?bool
    {
        $isActive = $this->getData(self::IS_ACTIVE);
        return $isActive === null ? null : (bool)$isActive;
    }

    /**
     * Set is_active
     *
     * @param bool $isActive
     * @return $this
     */
    public function setIsActive(bool $isActive): PartnerInterface
    {
        return $this->setData(self::IS_ACTIVE, $isActive);
    }
    
    /**
     * Get extension attributes
     *
     * @return \Wholesale\PartnerPortal\Api\Data\PartnerExtensionInterface|null
     */
    public function getExtensionAttributes(): ?\Wholesale\PartnerPortal\Api\Data\PartnerExtensionInterface
    {
        return $this->_getExtensionAttributes();
    }

    /**
     * Set extension attributes
     *
     * @param \Wholesale\PartnerPortal\Api\Data\PartnerExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        \Wholesale\PartnerPortal\Api\Data\PartnerExtensionInterface $extensionAttributes
    ): PartnerInterface {
        return $this->_setExtensionAttributes($extensionAttributes);
    }
}