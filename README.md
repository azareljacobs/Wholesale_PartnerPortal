# Wholesale Partner Portal Module Documentation

## 1. Module Overview

- **Name**: `Wholesale_PartnerPortal`
- **Description**: Magento 2 module for managing wholesale partners.
- **Setup Version**: `1.0.0` (from `etc/module.xml`)
- **Composer Version**: `1.0.0` (from `composer.json`)
- **Dependencies**:
    - `Magento_Backend` (from `etc/module.xml`)
    - `php: ~7.4.0||~8.1.0` (from `composer.json`)
    - `magento/framework: 103.0.*` (from `composer.json`)

## 2. Registration

The module is registered using the standard Magento 2 registration mechanism in `registration.php`:

```php
\Magento\Framework\Component\ComponentRegistrar::register(
    \Magento\Framework\Component\ComponentRegistrar::MODULE,
    'Wholesale_PartnerPortal',
    __DIR__
);
```

## 3. Database Schema

The module defines one primary database table: `wholesale_partner`.

**Table: `wholesale_partner`** (Comment: Wholesale Partner Table)

| Column          | Type        | Length | Nullable | Default | Identity | Unique | Comment                     |
|-----------------|-------------|--------|----------|---------|----------|--------|-----------------------------|
| `partner_id`    | `int`       | 10     | false    |         | true     | PK     | Partner ID                  |
| `name`          | `varchar`   | 255    | false    |         |          |        | Partner Name                |
| `slug`          | `varchar`   | 255    | false    |         |          | Yes    | Partner URL Key             |
| `logo`          | `varchar`   | 255    | true     |         |          |        | Partner Logo Image Path     |
| `description`   | `text`      |        | true     |         |          |        | Partner Description         |
| `website`       | `varchar`   | 255    | true     |         |          |        | Partner Website URL         |
| `contact_email` | `varchar`   | 255    | true     |         |          |        | Partner Contact Email       |
| `is_active`     | `boolean`   |        | false    | `1`     |          |        | Partner Is Active           |

**Constraints:**
- Primary Key: `partner_id`
- Unique Key: `WHOLESALE_PARTNER_SLUG` on `slug` column.

(Source: `etc/db_schema.xml`)

## 4. Access Control List (ACL)

The module defines ACL resources for managing partners in the Magento Admin.

- **Parent Resource**: `Magento_Backend::stores`
- **Main Resource**: `Wholesale_PartnerPortal::partner` (Title: "Partner Management", Sort Order: 50)
    - `Wholesale_PartnerPortal::partner_save` (Title: "Save Partner", Sort Order: 10)
    - `Wholesale_PartnerPortal::partner_delete` (Title: "Delete Partner", Sort Order: 20)

(Source: `etc/acl.xml`)

## 5. Dependency Injection (`di.xml`)

The `etc/di.xml` file configures various aspects of the module's dependency injection.

**Key Configurations:**

-   **Interface Preferences**:
    -   `Wholesale\PartnerPortal\Api\Data\PartnerInterface` maps to `Wholesale\PartnerPortal\Model\Partner`
    -   `Wholesale\PartnerPortal\Api\PartnerRepositoryInterface` maps to `Wholesale\PartnerPortal\Model\PartnerRepository`
    -   `Wholesale\PartnerPortal\Api\Data\PartnerSearchResultsInterface` maps to `Magento\Framework\Api\SearchResults`

-   **Virtual Types**:
    -   `Wholesale\PartnerPortal\Model\Api\SearchCriteria\PartnerCollectionProcessor`: A virtual type for `Magento\Framework\Api\SearchCriteria\CollectionProcessor`, configured with filter, sorting, and pagination processors.
    -   `Wholesale\PartnerPortal\Model\ResourceModel\Partner\Grid\Collection`: A virtual type for `Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult`, configured for the `wholesale_partner` table and `Partner` resource model.
    -   `WholesalePartnerLogoUploader`: A virtual type for `Wholesale\PartnerPortal\Model\ImageUploader` specifically for partner logos.

-   **Type Argument Configurations**:
    -   `Wholesale\PartnerPortal\Model\PartnerRepository`: Injected with the `PartnerCollectionProcessor`.
    -   `Wholesale\PartnerPortal\Model\ImageUploader`: Configured with:
        - `baseTmpPath`: Set to `wholesale/partner/tmp`
        - `basePath`: Set to `wholesale/partner` 
        - `allowedExtensions`: Array of allowed file types (`jpg`, `jpeg`, `gif`, `png`)
    -   `Wholesale\PartnerPortal\Controller\Adminhtml\Partner\Upload`: Injected with `WholesalePartnerLogoUploader`.
    -   `Magento\Framework\View\Element\UiComponent\DataProvider\CollectionFactory`: Configured with `partner_listing_data_source` mapping to `Wholesale\PartnerPortal\Model\ResourceModel\Partner\Grid\Collection`.
    -   `Wholesale\PartnerPortal\Model\Partner\DataProvider`: Configured with:
        - `name`: `partner_form_data_source`
        - `primaryFieldName`: `partner_id`
        - `requestFieldName`: `partner_id`
        - This provider is responsible for fetching and preparing data for the partner form, including handling file uploads like the logo image.

## 6. API Layer

The API layer defines service contracts for the module.

### `Api\PartnerRepositoryInterface.php`

This interface provides methods for managing `Partner` entities.

-   `save(PartnerInterface $partner)`: Saves a partner.
-   `getById($partnerId)`: Retrieves a partner by its ID.
-   `getBySlug($slug)`: Retrieves a partner by its URL slug.
-   `delete(PartnerInterface $partner)`: Deletes a partner.
-   `deleteById($partnerId)`: Deletes a partner by its ID.
-   `getList(SearchCriteriaInterface $searchCriteria)`: Retrieves a list of partners based on search criteria, returning `PartnerSearchResultsInterface`.

**Repository Improvements**:
- Added optional filtering in the repository for active/inactive partners
- Implemented repository-level caching for partner entities to improve performance
- Added new dedicated methods like `getActiveById()` and `getActiveBySlug()` to centralise business logic
- Created `getActiveList()` method for filtering collections
- Ensured consistent behaviour across all application entry points

### GraphQL Schema

The module provides GraphQL API endpoints for querying partner data. The schema is defined in `etc/schema.graphqls` and includes:

- `partner` query: Fetches a single partner by ID or slug
  - Parameters: 
    - `id` (Int): Partner ID
    - `slug` (String): Partner URL key
  - Returns: Partner object

- `partners` query: Fetches a list of partners with filtering, sorting and pagination
  - Parameters:
    - `filter` (PartnerFilterInput): Filter criteria
    - `pageSize` (Int, default: 20): Results per page
    - `currentPage` (Int, default: 1): Current page number
    - `sort` (PartnerSortInput): Sort criteria
  - Returns: Partners object with items, total_count, and page_info

Both GraphQL queries are implemented by dedicated resolver classes that enforce security rules, such as only returning active partners.

### `Api\Data\PartnerInterface.php`

This interface defines the data structure for a partner entity. It includes getter and setter methods for all fields defined in the `wholesale_partner` table:

- `getId()` / `setId($id)`
- `getName()` / `setName($name)`
- `getSlug()` / `setSlug($slug)`
- `getLogo()` / `setLogo($logo)`
- `getDescription()` / `setDescription($description)`
- `getWebsite()` / `setWebsite($website)`
- `getContactEmail()` / `setContactEmail($email)`
- `getIsActive()` / `setIsActive($isActive)`

### Extension Attributes

The module supports extension attributes through `PartnerExtensionInterface`, allowing third-party modules to extend partner data without modifying core code. This is configured in `etc/extension_attributes.xml`.

## 7. Model Layer

### `Model\Partner.php`

This is the primary model for the `Partner` entity.

-   Implements `Wholesale\PartnerPortal\Api\Data\PartnerInterface` and `Magento\Framework\DataObject\IdentityInterface`.
-   Inherits from `Magento\Framework\Model\AbstractExtensibleModel` to support extension attributes.
-   **Cache Tag**: `wholesale_partner` (Constant: `CACHE_TAG`).
-   **Event Prefix**: `wholesale_partner`.
-   **Resource Model**: Initialises with `Wholesale\PartnerPortal\Model\ResourceModel\Partner::class`.
-   Implements magic `__call` method to handle dynamic getters/setters, reducing code duplication:
    ```php
    /**
     * Get dynamic data via magic method
     *
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public function __call($method, $args)
    {
        // Convert getFieldName to field_name for data access
        if (strpos($method, 'get') === 0) {
            $key = $this->snakeCase(substr($method, 3));
            return $this->getData($key);
        }
        // Convert setFieldName to field_name for data setting
        if (strpos($method, 'set') === 0) {
            $key = $this->snakeCase(substr($method, 3));
            return $this->setData($key, isset($args[0]) ? $args[0] : null);
        }
        return parent::__call($method, $args);
    }
    ```
-   Provides explicit implementations for all methods defined in `PartnerInterface` for backward compatibility.
-   Supports extension attributes through `getExtensionAttributes()` and `setExtensionAttributes()` methods.
-   Includes comprehensive DocBlock comments for all interface constants.

### `Model\PartnerRepository.php`

This class implements `PartnerRepositoryInterface` and handles the business logic for retrieving, saving, and deleting partner data. It uses the `Partner` model, `Partner` resource model, and collection classes.

Key methods include:
- `getById($partnerId)`: Retrieves a partner by ID
- `getBySlug($slug)`: Retrieves a partner by URL slug
- `save(PartnerInterface $partner)`: Saves partner data
- `delete(PartnerInterface $partner)`: Deletes a partner
- `getList(SearchCriteriaInterface $searchCriteria)`: Retrieves a filtered, sorted list of partners
- `getActiveById($partnerId)`: Retrieves only active partners by ID
- `getActiveBySlug($slug)`: Retrieves only active partners by URL slug
- `getActiveList(SearchCriteriaInterface $searchCriteria)`: Retrieves only active partners based on search criteria

### `Model\ImageUploader.php`

This model handles the logic for uploading partner logo images. Key functionality includes:
- Moving files from temporary to permanent locations
- Managing allowed file types (jpg, jpeg, gif, png)
- Generating unique file names to avoid conflicts
- Creating appropriate directory structure for storage

**Improvements implemented**:
- Added constants for all path elements (`BASE_TMP_PATH`, `BASE_PATH`, `ALLOWED_EXTENSIONS`) to eliminate hardcoded strings
- Reduced excessive logging to improve performance
- Implemented `getMediaUrl()` method to generate media URLs for partner logos
- Added `deleteFile()` method for proper file cleanup
- Improved error handling with more concise exception reporting
- Separated file validation from storage operations

### `Model\ResourceModel\`

This directory contains the resource models and collections:
-   `Partner.php`: The resource model for the `Partner` entity, responsible for direct database interactions (CRUD operations for a single entity). It maps to the `wholesale_partner` table and `partner_id` primary key. Includes robust error handling with try/catch blocks and proper logging for database operations, particularly for slug existence validation.
-   `Partner\Collection.php`: The collection model for `Partner` entities, used for fetching multiple partner records.

### `Model\Resolver\`

This directory contains GraphQL resolvers:
-   `Partner.php`: Resolver for the `partner` query to fetch a single partner. It implements security checks to ensure only active partners are returned.
-   `Partners.php`: Resolver for the `partners` query to fetch multiple partners with filtering, sorting and pagination. Like the single partner resolver, it also enforces the active status check.

### `Model\Service\`

The module incorporates several dedicated service classes to follow the Command/Query Separation Pattern:
-   `PartnerCommandService`: Handles data modification operations (create, update, delete)
-   `PartnerQueryService`: Handles data retrieval operations (get, list, find)
-   `PartnerVisibilityService`: Centralises partner visibility rules
-   `PartnerDataSanitizerService`: Handles data validation and sanitisation
-   `PartnerLogoService`: Manages file operations for partner logos
-   `PartnerMediaUrlService`: Centralises logo URL generation logic

## 8. Directory Structure Overview

-   **`Api/`**: Contains service contracts (interfaces) and data interfaces for the module.
    -   `Data/`: Data transfer object (DTO) interfaces.
-   **`Block/`**: Contains block classes responsible for rendering views and providing data to templates.
    -   `Adminhtml/`: Blocks specific to the admin panel.
-   **`Controller/`**: Contains controller classes that handle HTTP requests.
    -   `Adminhtml/`: Admin panel controllers (e.g., for CRUD operations on partners, image uploads).
    -   `Partners/`: Frontend controllers for displaying partner information.
-   **`etc/`**: Configuration files for the module.
    -   `adminhtml/`: Admin-specific configurations (e.g., `menu.xml`, `routes.xml`).
    -   `frontend/`: Frontend-specific configurations (e.g., `routes.xml`).
    -   `acl.xml`: Access Control List definitions.
    -   `db_schema.xml`: Database schema definitions.
    -   `di.xml`: Dependency injection configurations.
    -   `module.xml`: Basic module definition and dependencies.
    -   `schema.graphqls`: GraphQL schema definition for partner queries
-   **`Model/`**: Contains business logic models, resource models, and collections.
    -   `ResourceModel/`: Resource models and collections for database interaction.
    -   `Resolver/`: GraphQL resolvers for partner data
    -   `Service/`: Dedicated service classes for business logic operations
-   **`Ui/`**: Contains UI component configurations and related classes.
    -   `Component/`: UI component classes, often data providers or form modifiers.
-   **`view/`**: Contains template files, layout XML files, and web assets (CSS, JS, images).
    -   `adminhtml/`: Admin-specific views (layouts, templates, web assets).
    -   `frontend/`: Frontend-specific views (layouts, templates, web assets).

## 9. Frontend and Adminhtml

The module has distinct functionalities for both the frontend and adminhtml areas, as indicated by the presence of specific directories within `Block/`, `Controller/`, `etc/`, and `view/`.

-   **Adminhtml**: Provides an interface for managing partners (CRUD operations, viewing listings, managing settings). This is evident from `Adminhtml` subdirectories in `Block`, `Controller`, `etc/adminhtml`, and `view/adminhtml`, as well as UI component configurations related to admin grids and forms.

    **UI Component Configuration Improvements**:
    - Standardised URL generation in JavaScript components to use proper Magento URL format
    - Updated validation URL in url-key.js from 'wholesale/partner/validate' to 'wholesale_partner/partner/validate'
    - Enhanced validation rules in partner_form.xml to match JavaScript validation
    - Added max_text_length restriction (64 characters) and validate-no-html-tags validation to slug field
    - Updated help text to provide clearer guidance on validation requirements

    **JavaScript Component Improvements**:
    - Enhanced client-side validation for URL keys to mirror server-side rules
    - Added debounce functionality to prevent excessive processing during rapid typing
    - Implemented real-time AJAX validation to check URL key uniqueness
    - Added immediate feedback on URL key validity
    - Maintained automated slug generation from partner names

    **Image Delete Functionality Improvements**:
    - Added new `DeleteImage` controller for handling AJAX requests
    - Implemented real-time physical file deletion when the trash icon is clicked
    - Updated JavaScript for immediate visual feedback when deleting images
    - Ensured synchronization between database records and physical files

-   **Frontend**: Displays partner information to website visitors. This is implemented through `frontend` subdirectories in `etc/frontend` and `view/frontend`, and the `Controller/Partners` directory.

    **Block Class Improvements**:
    - Standardised access modifiers to use consistent `private` visibility for class properties
    - Enhanced PHPDoc comments in block methods for better clarity
    - All block classes now properly utilize the `PartnerMediaUrlService` for logo URL generation
    - Removed direct ObjectManager usage in favor of proper dependency injection

    **Template Improvements**:
    - Added structured data markup with schema.org ItemList and Organization types for better SEO
    - Implemented lazy loading for partner logos with `loading="lazy"` attribute
    - Added pagination controls for the partner list
    - Implemented fallback image handling for partners without logos
    - Improved description handling to preserve allowed HTML formatting

## 10. Usage Instructions

### 10.1. Managing Partners in the Magento Admin

Partners are managed through the Magento Admin panel.

-   **Navigation**: To access the partner management grid, navigate to:
`Stores > Partners > Manage Partners`
(This menu item is defined in `etc/adminhtml/menu.xml` and points to the `wholesale_partner/partner/index` action with the frontName `wholesale_partner` as configured in `adminhtml/routes.xml`.)

-   **Partner Grid (`partner_listing.xml`)**:
    -   Displays a list of all wholesale partners.
    -   Columns include: ID, Name, Slug, Logo (thumbnail), Website, Contact Email, and Active status.
    -   Provides standard grid functionalities: filtering, sorting, searching, and pagination.
    -   **Add New Partner**: A button labelled "Add New Partner" allows administrators to create new partner entries. This button links to the `*/*/new` action (typically `wholesale_partner/partner/new`).
    -   **Actions Column**: Each row in the grid has an "Actions" column, providing options to:
        -   **Edit**: Opens the partner edit form for the selected partner.
        -   **Delete**: Allows deletion of the selected partner (with a confirmation prompt).

-   **Partner Form (`partner_form.xml`)**:
    -   Used for both creating new partners and editing existing ones.
    -   **URL**: Accessed via `wholesale_partner/partner/new` (for new partners) or `wholesale_partner/partner/edit/partner_id/[id]` (for existing partners).
    -   **Fields (within the "General Information" fieldset)**:
        -   `Partner Name` (text input, required)
        -   `URL Key` (text input, required, auto-generated from Partner Name if left empty, validated for uniqueness and identifier format)
        -   `Logo` (file uploader, for partner's logo image)
        -   `Description` (textarea)
        -   `Website` (text input, validated as a URL)
        -   `Contact Email` (text input, validated as an email)
        -   `Active` (select dropdown Yes/No, required, defaults to Yes)
    -   **Form Buttons**:
        -   `Back`: Returns to the partner grid without saving.
        -   `Delete`: Deletes the current partner (if editing an existing one).
        -   `Save`: Saves the partner data and typically redirects back to the grid.
        -   `Save and Continue Edit`: Saves the partner data and reloads the edit form.

-   **Custom URL Key Component**:
    -   The form includes a custom JavaScript component (`Wholesale_PartnerPortal/js/form/element/url-key.js`) for the URL Key field.
    -   This component automatically generates a URL key (slug) based on the partner name when the field is left empty.
    -   It sanitizes the input by converting spaces to hyphens, removing special characters, and ensuring the format is valid for use in URLs.
    -   Validation ensures the slug is unique across partners to prevent URL conflicts.
    -   Enhanced with real-time AJAX validation to check for duplicate URL keys.
    -   Length restriction (64 characters) prevents potential database issues.

### 10.2. How Partners are Displayed on the Frontend

The module provides frontend pages to display a list of partners and individual partner details.

-   **Base URL**: The frontend pages for this module are accessible under the `wholesale/` path (e.g., `yourstore.com/wholesale/...`). This is defined in `etc/frontend/routes.xml`.

-   **Partner List Page**:
    -   **URL**: `wholesale/partners/index`
    -   **Controller**: `Wholesale\PartnerPortal\Controller\Partners\Index`
    -   **Layout**: `view/frontend/layout/wholesale_partners_index.xml`
    -   **Block**: `Wholesale\PartnerPortal\Block\PartnerList`
    -   **Template**: `view/frontend/templates/partner/list.phtml`
    -   **Functionality**: This page displays a list of active wholesale partners. The specific details shown for each partner in the list depend on the `list.phtml` template.
    -   The page title is set to "Partners".
    -   Includes custom CSS from `Wholesale_PartnerPortal::css/partner.css`.
    -   Includes structured data markup with schema.org ItemList for better SEO.
    -   Implements lazy loading for partner logos.
    -   Supports pagination controls.
    -   Features responsive image handling with fallbacks for missing images.

-   **Partner View Page**:
    -   **URL**: `wholesale/partners/view/slug/[slug]` (e.g., `wholesale/partners/view/slug/acme-inc`)
    -   **Controller**: `Wholesale\PartnerPortal\Controller\Partners\View`
    -   **Layout**: `view/frontend/layout/wholesale_partners_view.xml`
    -   **Block**: `Wholesale\PartnerPortal\Block\PartnerView`
    -   **Template**: `view/frontend/templates/partner/view.phtml`
    -   **Functionality**: This page displays detailed information for a single partner, identified by the `slug` parameter in the URL. The specific details shown depend on the `view.phtml` template (e.g., name, logo, description, website, contact email).
    -   **Security and Edge Cases**:
        -   If a partner with the given slug is not found, the controller redirects to Magento's standard 404 page.
        -   If the partner exists but is marked as inactive (`is_active = false`), the controller also redirects to the 404 page, ensuring that unpublished partners are not accessible.
        -   The controller dynamically sets the page title to the partner's name, overriding the static "Partner Details" title set in the layout XML.
    -   Includes custom CSS from `Wholesale_PartnerPortal::css/partner.css`.
    -   The logo URL is constructed using the same path configuration as the admin uploader: `wholesale/partner/[filename]`.
    -   Features structured data markup with schema.org Organization type for better SEO.
    -   Implements improved description handling to preserve allowed HTML formatting.

### 10.3. GraphQL Endpoints

The module provides GraphQL endpoints for accessing partner data programmatically:

-   **Partner Query**:
    -   **Description**: Fetch a single partner by ID or slug
    -   **Example**:
      ```graphql
      {
        partner(id: 1) {
          partner_id
          name
          slug
          logo
          description
          website
          contact_email
        }
      }
      ```
    -   **Or by slug**:
      ```graphql
      {
        partner(slug: "partner-name") {
          partner_id
          name
          description
        }
      }
      ```
    -   **Security**: The resolver (`Model\Resolver\Partner.php`) verifies that the partner is active (`is_active = true`) before returning data. If inactive, it throws a `GraphQlNoSuchEntityException`.

-   **Partners Query**:
    -   **Description**: Fetch a list of partners with filtering, sorting, and pagination
    -   **Example**:
      ```graphql
      {
        partners(
          filter: { name: { like: "%company%" } }
          sort: { name: ASC }
          pageSize: 10
          currentPage: 1
        ) {
          items {
            partner_id
            name
            website
          }
          total_count
          page_info {
            page_size
            current_page
            total_pages
          }
        }
      }
      ```
    -   **Filtering Options**: The API supports various filter conditions including:
        -   `eq`: Equals
        -   `neq`: Not equals
        -   `like`: Contains (uses SQL LIKE)
        -   `gt/lt/gteq/lteq`: Comparison operators
        -   `in/nin`: In/not in array of values
        -   `or`: Combine conditions with OR logic
    -   **Security**: Like the single partner query, this resolver also filters out inactive partners.

For detailed GraphQL API documentation, see `docs/graphql.md` in the module directory.

## 11. Key Components Summary

-   **Controllers**: 
    -   **Admin Controllers**: Handle administration actions like listing (`Adminhtml/Partner/Index.php`), editing (`Edit.php`), saving (`Save.php`), deleting (`Delete.php`), and file uploads (`Upload.php`).
    -   **Frontend Controllers**: Handle frontend display including the partner list (`Partners/Index.php`) and partner detail view (`Partners/View.php`).
    -   **Security**: Frontend controllers implement checks to ensure only active partners are displayed to visitors.

    **Controller Improvements**:
    - Implemented `PartnerVisibilityService` to centralise partner visibility rules
    - Added `PartnerDataSanitizerService` to extract data validation and sanitisation logic
    - Created `PartnerLogoService` to handle file operations for partner logos
    - Implemented consistent approach for error handling across the module
    - Eliminated duplicated visibility checking code between controllers and GraphQL resolvers
    - Moved input sanitisation from controllers to dedicated services

-   **Blocks**:
    -   **Admin Blocks**: Prepare data and UI for admin forms and grids.
    -   **Frontend Blocks**: Provide data to templates for rendering partner information:
        -   `PartnerList.php`: Retrieves a filtered collection of active partners and provides methods for generating partner URLs and logo URLs.
        -   `PartnerView.php`: Gets partner data either from URL parameters or registry, and provides methods for accessing partner attributes and generating logo URLs.
    -   **Block Data Flow**: Frontend blocks retrieve partner data and prepare it for use in templates, including filtering for active status and generating media URLs.
    -   **Data Sharing**: Properly configured dependencies are injected via constructor rather than using ObjectManager directly.

-   **Templates**:
    -   **Frontend Templates**: 
        -   `list.phtml`: Renders a grid of partner cards with logos, names, and links to individual partner pages. Includes schema.org markup, lazy loading for images, and pagination support.
        -   `view.phtml`: Displays detailed partner information including logo, name, description, website, and contact email, with proper HTML escaping for each field. Includes schema.org markup and responsive image handling.
    -   **HTML Escaping**: All output is properly escaped using appropriate escape methods (`escapeHtml`, `escapeUrl`, etc.), with special handling for HTML in descriptions.

-   **UI Components**:
    -   **Partner Listing**: Configured via `partner_listing.xml` and `partner_listing_data_source` in `di.xml`.
    -   **Partner Form**: Configured via `partner_form.xml` and `partner_form_data_source` in `di.xml`.
    -   **Custom Components**: Includes a custom URL key component (`url-key.js`) for automatic slug generation and validation, with enhanced AJAX validation and debouncing for better performance.

-   **GraphQL**:
    -   **Schema**: Defined in `schema.graphqls` with types for Partner, Partners (collection), and input types for filtering and sorting.
    -   **Resolvers**: Implemented in `Model/Resolver/` classes with security checks to ensure only active partners are returned.

-   **Architecture**:
    -   **Command/Query Separation**: Implemented dedicated services for data retrieval (queries) and data modification (commands).
    -   **Service Layer**: Created specialized services for specific business operations to improve separation of concerns.
    -   **Reduced Coupling**: Controllers and GraphQL resolvers now use service classes rather than directly accessing repositories.
    -   **Dependency Injection**: Properly configured dependencies in di.xml and consistent use of constructor injection across all classes.

## 12. Developer Notes

-   The module follows standard Magento 2 coding practices with improved architecture and performance optimisations.
-   Key business logic for partner management is encapsulated within service classes, reducing coupling between components.
-   The resource model includes robust error handling with try/catch blocks and proper logging for database operations.
-   The `DataProvider` (`Wholesale\PartnerPortal\Model\Partner\DataProvider`) plays a significant role in preparing data for the admin UI form, especially for the logo file uploader.
-   Frontend display logic is primarily within the Block classes and their templates, with proper dependency injection.
-   The frontend controllers use service classes that leverage `getActiveBySlug()` from the repository, optimised for URL-based navigation.
-   Logo image paths are configured through constants: temporary uploads go to `wholesale/partner/tmp`, while permanent images are stored in `wholesale/partner`.
-   Logo images are restricted to jpg, jpeg, gif, and png file formats as configured in the image uploader.
-   The GraphQL resolvers use the same service classes as the controllers, ensuring consistent business logic across different access methods.
-   For security reasons, active status checks (`is_active = true`) are centralised in the repository and visibility service, ensuring consistent rules across all interfaces.
-   The URL key JavaScript component provides debounced validation and generation of slugs from partner names.
-   HTML content in partner descriptions is handled securely while preserving formatting.
-   Logo URL generation logic has been centralised in the `PartnerMediaUrlService` to eliminate duplication.

**Code Quality Improvements**:
- Added comprehensive PHPDoc comments to all methods with detailed parameter descriptions
- Enhanced the codebase with return type hints and scalar type hints
- Standardised access modifiers to follow consistent patterns
- Improved method signatures for better type safety and interface consistency
- Created custom search results implementation for better type compatibility
- Added proper type casting for numeric and boolean values
- Enhanced exception handling with specialized error types

This documentation provides a comprehensive overview of the `Wholesale_PartnerPortal` module. The module follows a service-oriented architecture with proper separation of concerns, making it maintainable, performant, and extensible.
