<?php
declare(strict_types=1);
/**
 * Partner data interface
 *
 * @category  Wholesale
 * @package   Wholesale_PartnerPortal
 */

namespace Wholesale\PartnerPortal\Api\Data;

/**
 * Interface PartnerInterface
 * @api
 */
interface PartnerInterface
{
    /**
     * Constants for keys of data array
     */
    
    /**
     * Partner ID field name
     *
     * @var string
     */
    const PARTNER_ID = 'partner_id';
    
    /**
     * Partner name field name
     *
     * @var string
     */
    const NAME = 'name';
    
    /**
     * Partner URL key/slug field name
     *
     * @var string
     */
    const SLUG = 'slug';
    
    /**
     * Partner logo image field name
     *
     * @var string
     */
    const LOGO = 'logo';
    
    /**
     * Partner description field name
     *
     * @var string
     */
    const DESCRIPTION = 'description';
    
    /**
     * Partner website URL field name
     *
     * @var string
     */
    const WEBSITE = 'website';
    
    /**
     * Partner contact email field name
     *
     * @var string
     */
    const CONTACT_EMAIL = 'contact_email';
    
    /**
     * Partner active status field name
     *
     * @var string
     */
    const IS_ACTIVE = 'is_active';

    /**
     * Get partner ID
     *
     * @return int|null
     */
    public function getId(): ?int;

    /**
     * Set partner ID
     *
     * @param int $id
     * @return $this
     */
    public function setId(int $id): PartnerInterface;

    /**
     * Get partner name
     *
     * @return string|null
     */
    public function getName(): ?string;

    /**
     * Set partner name
     *
     * @param string $name
     * @return $this
     */
    public function setName(string $name): PartnerInterface;

    /**
     * Get partner slug
     *
     * @return string|null
     */
    public function getSlug(): ?string;

    /**
     * Set partner slug
     *
     * @param string $slug
     * @return $this
     */
    public function setSlug(string $slug): PartnerInterface;

    /**
     * Get partner logo
     *
     * @return string|null
     */
    public function getLogo(): ?string;

    /**
     * Set partner logo
     *
     * @param string|null $logo
     * @return $this
     */
    public function setLogo(?string $logo): PartnerInterface;

    /**
     * Get partner description
     *
     * @return string|null
     */
    public function getDescription(): ?string;

    /**
     * Set partner description
     *
     * @param string|null $description
     * @return $this
     */
    public function setDescription(?string $description): PartnerInterface;

    /**
     * Get partner website
     *
     * @return string|null
     */
    public function getWebsite(): ?string;

    /**
     * Set partner website
     *
     * @param string|null $website
     * @return $this
     */
    public function setWebsite(?string $website): PartnerInterface;

    /**
     * Get partner contact email
     *
     * @return string|null
     */
    public function getContactEmail(): ?string;

    /**
     * Set partner contact email
     *
     * @param string|null $contactEmail
     * @return $this
     */
    public function setContactEmail(?string $contactEmail): PartnerInterface;

    /**
     * Get partner is_active status
     *
     * @return bool|null
     */
    public function getIsActive(): ?bool;

    /**
     * Set partner is_active status
     *
     * @param bool $isActive
     * @return $this
     */
    public function setIsActive(bool $isActive): PartnerInterface;
}