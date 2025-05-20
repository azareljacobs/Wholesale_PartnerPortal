<?php
/**
 * Partner data sanitiser service
 *
 * @category  Wholesale
 * @package   Wholesale_PartnerPortal
 */

namespace Wholesale\PartnerPortal\Model\Service;

use Wholesale\PartnerPortal\Api\Data\PartnerInterface;

/**
 * Service class to handle partner data sanitisation
 */
class PartnerDataSanitizerService
{
    /**
     * @var array
     */
    private $allowedFields = [
        PartnerInterface::PARTNER_ID,
        PartnerInterface::NAME,
        PartnerInterface::SLUG,
        PartnerInterface::LOGO,
        PartnerInterface::DESCRIPTION,
        PartnerInterface::WEBSITE,
        PartnerInterface::CONTACT_EMAIL,
        PartnerInterface::IS_ACTIVE
    ];

    /**
     * Constructor
     */
    public function __construct()
    {
    }

    /**
     * Sanitise partner data
     *
     * @param array $data
     * @return array
     */
    public function sanitize(array $data): array
    {
        $sanitized = [];
        
        // Only keep allowed fields
        foreach ($this->allowedFields as $field) {
            if (isset($data[$field])) {
                $sanitized[$field] = $data[$field];
            }
        }
        
        return $this->sanitizeSpecificFields($sanitized);
    }

    /**
     * Apply field-specific sanitisation rules
     *
     * @param array $data
     * @return array
     */
    private function sanitizeSpecificFields(array $data): array
    {
        if (isset($data[PartnerInterface::NAME])) {
            $data[PartnerInterface::NAME] = strip_tags($data[PartnerInterface::NAME]);
        }
        
        if (isset($data[PartnerInterface::SLUG])) {
            // Ensure slug only contains allowed characters
            $data[PartnerInterface::SLUG] = preg_replace(
                '/[^a-z0-9-]/', 
                '', 
                strtolower($data[PartnerInterface::SLUG])
            );
        }
        
        if (isset($data[PartnerInterface::DESCRIPTION])) {
            // Strip potentially harmful tags but keep basic formatting
            $data[PartnerInterface::DESCRIPTION] = strip_tags(
                $data[PartnerInterface::DESCRIPTION], 
                '<p><br><strong><em><ul><ol><li>'
            );
        }
        
        if (isset($data[PartnerInterface::WEBSITE])) {
            // Ensure website is a valid URL
            if (!filter_var($data[PartnerInterface::WEBSITE], FILTER_VALIDATE_URL)) {
                unset($data[PartnerInterface::WEBSITE]);
            }
        }
        
        if (isset($data[PartnerInterface::CONTACT_EMAIL])) {
            // Ensure email is valid
            if (!filter_var($data[PartnerInterface::CONTACT_EMAIL], FILTER_VALIDATE_EMAIL)) {
                unset($data[PartnerInterface::CONTACT_EMAIL]);
            }
        }
        
        if (isset($data[PartnerInterface::IS_ACTIVE])) {
            // Ensure is_active is boolean
            $data[PartnerInterface::IS_ACTIVE] = (bool)$data[PartnerInterface::IS_ACTIVE];
        }
        
        return $data;
    }

    /**
     * Process the logo data from form submission
     *
     * @param array $data
     * @return array
     */
    public function processLogoData(array $data): array
    {
        if (!isset($data[PartnerInterface::LOGO])) {
            return $data;
        }

        // Handle different logo data structures
        if (is_array($data[PartnerInterface::LOGO])) {
            // Case 1: Array with delete flag - mark for deletion
            if (isset($data[PartnerInterface::LOGO]['delete']) && $data[PartnerInterface::LOGO]['delete'] == '1') {
                // Keep the delete flag in the array format for Controller/Save.php to process
                return $data;
            }
            
            // Case 2: Array with delete flag in first element
            if (isset($data[PartnerInterface::LOGO][0]['delete']) && $data[PartnerInterface::LOGO][0]['delete'] == '1') {
                // Restructure to standard format
                $data[PartnerInterface::LOGO] = ['delete' => '1'];
                return $data;
            }

            // Case 3: Array with file info in first element (new upload)
            if (isset($data[PartnerInterface::LOGO][0]['name']) &&
                isset($data[PartnerInterface::LOGO][0]['tmp_name'])) {
                $data[PartnerInterface::LOGO] = $data[PartnerInterface::LOGO][0]['name'];
                return $data;
            }

            // Case 4: Array with name property directly
            if (isset($data[PartnerInterface::LOGO]['name']) &&
                isset($data[PartnerInterface::LOGO]['tmp_name'])) {
                $data[PartnerInterface::LOGO] = $data[PartnerInterface::LOGO]['name'];
                return $data;
            }

            // If we couldn't understand the array format, keep the existing value
            if (isset($data['partner_id'])) {
                // For existing partner, preserve current logo
                unset($data[PartnerInterface::LOGO]);
            } else {
                // For new partner, clear logo field
                $data[PartnerInterface::LOGO] = '';
            }
        }
        
        return $data;
    }
}