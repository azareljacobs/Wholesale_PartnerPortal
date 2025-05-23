# Wholesale Partner Portal Module Documentation

## 1. Module Overview

- **Name**: `Wholesale_PartnerPortal`
- **Description**: Magento 2 module for managing wholesale partners.
- **Setup Version**: `1.0.0` (from `etc/module.xml`)
- **Composer Version**: `1.0.0` (from `composer.json`)
- **Dependencies**:
    - `Magento_Backend` (from `etc/module.xml`)
    - `php: >=8.0` (from `composer.json`)
    - `magento/framework: 103.0.*` (from `composer.json`)

## 2. Registration

The module is registered using the standard Magento 2 registration mechanism in [`registration.php`](registration.php:0):

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
    -   `Wholesale\PartnerPortal\Api\Data\PartnerSearchResultsInterface` maps to `Wholesale\PartnerPortal\Model\PartnerSearchResults`

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

        This section also includes explicit constructor argument wiring for command/query services such as `PartnerCommandService` and `PartnerQueryService`. While Magento can resolve these dependencies automatically via constructor type hints, defining them explicitly in `di.xml` improves clarity, supports integration test overrides, and enables future flexibility for swapping dependencies without changing the class code.

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

The repository offers features like optional filtering for active/inactive partners and repository-level caching with proper invalidation. The repository implements in-memory caching of loaded entities with cache invalidation on save and delete operations to prevent stale data. It also provides dedicated methods such as `getActiveById()`, `getActiveBySlug()`, and `getActiveList()` to keep business logic clean and behaviour consistent.

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
-   **Cache Tag**: `wholesale_partner` (Constant: `CACHE_TAG`). Used for Full Page Cache (FPC) invalidation.
-   **Cache Identities**: The `getIdentities()` method returns both the general cache tag and specific partner ID tag, ensuring proper cache invalidation when partners are modified.
-   **Event Prefix**: `wholesale_partner`.
-   **Resource Model**: Initialises with `Wholesale\PartnerPortal\Model\ResourceModel\Partner::class`.
-   Provides explicit implementations for all methods defined in `PartnerInterface`, with proper type hints and return types.
-   Each getter/setter method directly accesses data using the constants defined in the interface (e.g., `self::NAME`, `self::SLUG`), ensuring consistent field access.
-   Supports extension attributes through `getExtensionAttributes()` and `setExtensionAttributes()` methods.
-   Includes comprehensive DocBlock comments for all interface constants.

### `Model\PartnerRepository.php`

This class implements `PartnerRepositoryInterface` and handles the business logic for retrieving, saving, and deleting partner data. It uses the `Partner` model, `Partner` resource model, and collection classes.

The repository implements an in-memory caching mechanism using a private `$partnerCache` array to store loaded entities. This improves performance by avoiding redundant database queries within the same request. The cache is properly invalidated in the `save()` and `delete()` methods to prevent stale data when entities are modified or removed.

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

This uploader uses constants for path elements (like `BASE_TMP_PATH` and `ALLOWED_EXTENSIONS`) rather than hardcoded strings. It also has a `getMediaUrl()` method for logo URLs, a `deleteFile()` method for cleaning up, and keeps file validation separate from storage. Error handling is straightforward.

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
-   **`ViewModel/`**: Contains view model classes that provide data to templates.
-   **`Ui/`**: Contains UI component configurations and related classes.
    -   `Component/`: UI component classes, often data providers or form modifiers.
-   **`view/`**: Contains template files, layout XML files, and web assets (CSS, JS, images).
    -   `adminhtml/`: Admin-specific views (layouts, templates, web assets).
    -   `frontend/`: Frontend-specific views (layouts, templates, web assets).

## 9. Frontend and Adminhtml

The module has distinct functionalities for both the frontend and adminhtml areas, as indicated by the presence of specific directories within `Block/`, `Controller/`, `etc/`, and `view/`.

-   **Adminhtml**: Provides an interface for managing partners (CRUD operations, viewing listings, managing settings). This is evident from `Adminhtml` subdirectories in `Block`, `Controller`, `etc/adminhtml`, and `view/adminhtml`, as well as UI component configurations related to admin grids and forms.

    - UI component configurations use standardised URL generation in JavaScript. Validation URLs are updated (e.g., [`url-key.js`](view/adminhtml/web/js/form/element/url-key.js:0) points to `wholesale_partner/partner/validate`), and [`partner_form.xml`](view/adminhtml/ui_component/partner_form.xml:0) has validation rules, like a 64-character limit and `validate-no-html-tags` for slugs. The help text is clear.
    - The JavaScript components for URL keys have client-side validation, a debounce function to avoid too many AJAX calls, real-time uniqueness checks, and instant feedback on whether a key is valid. It auto-generates slugs from partner names.

    - Deleting images is smooth: there's a `DeleteImage` controller for AJAX calls, files are deleted from the server straight away, JavaScript gives immediate visual feedback, and the database stays in sync with the file system.

The module also includes a `requirejs-config.js` file under `view/adminhtml`. These aliases serve as a foundation for extensibility and frontend testability. For instance, they enable test suites or downstream modules to cleanly override the file uploader or URL key components without altering layout XML or template code. This supports mocking and decouples JS module references from hardcoded paths, improving maintainability in both development and automated testing contexts.

-   **Frontend**: Displays partner information to website visitors. This is implemented through `frontend` subdirectories in `etc/frontend` and `view/frontend`, and the `Controller/Partners` directory.
    - Block classes consistently use `private` for properties, have detailed PHPDoc comments, and use the [`PartnerMediaUrlService`](Model/Service/PartnerMediaUrlService.php:0) for logo URLs. They use dependency injection instead of calling ObjectManager directly.
    - The frontend templates include structured data (schema.org ItemList and Organization types) for SEO. Partner logos use lazy loading (`loading="lazy"`), there are pagination controls for the list, fallback images if a logo is missing, and descriptions handle HTML formatting.

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
    -   **Admin Partner View Page**: When viewing a partner's details in the admin panel, a link labelled "Open Partner Page in New Window" is present. This link allows administrators to open the corresponding frontend partner page in a new browser tab.

-   **Custom URL Key Component**:
    -   The form includes a custom JavaScript component (`Wholesale_PartnerPortal/js/form/element/url-key.js`) for the URL Key field.
    -   This component automatically generates a URL key (slug) based on the partner name when the field is left empty.
    -   It sanitises the input by converting spaces to hyphens, removing special characters, and ensuring the format is valid for use in URLs.
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
    -   **Block**: `Magento\Framework\View\Element\Template` (using `Wholesale\PartnerPortal\ViewModel\Partner` view model)
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


## 11. Key Components Summary

-   **Controllers**: 
    -   **Admin Controllers**: Handle administration actions like listing (`Adminhtml/Partner/Index.php`), editing (`Edit.php`), saving (`Save.php`), deleting (`Delete.php`), and file uploads (`Upload.php`).
    -   **Frontend Controllers**: Handle frontend display including the partner list (`Partners/Index.php`) and partner detail view (`Partners/View.php`).
    -   **Security**: Frontend controllers implement checks to ensure only active partners are displayed to visitors.

    - Controllers make use of the [`PartnerVisibilityService`](Model/Service/PartnerVisibilityService.php:0) for consistent visibility rules, the [`PartnerDataSanitizerService`](Model/Service/PartnerDataSanitizerService.php:0) for cleaning up data, and the [`PartnerLogoService`](Model/Service/PartnerLogoService.php:0) for handling logo uploads. Error handling is consistent, duplicated visibility checks are minimised, and input sanitisation happens in dedicated services.

-   **Blocks**:
    -   **Admin Blocks**: Prepare data and UI for admin forms and grids.
    -   **Frontend Blocks**: Provide data to templates for rendering partner information:
        -   `PartnerList.php`: Retrieves a filtered collection of active partners and provides methods for generating partner URLs and logo URLs. Implements `IdentityInterface` to properly invalidate FPC cache when partners are modified.
        -   `PartnerView.php`: Gets partner data either from URL parameters or registry, and provides methods for accessing partner attributes and generating logo URLs. Implements `IdentityInterface` to properly invalidate FPC cache when the displayed partner is modified.
    -   **Block Data Flow**: Frontend blocks retrieve partner data and prepare it for use in templates, including filtering for active status and generating media URLs.
    -   **Data Sharing**: Properly configured dependencies are injected via constructor rather than using ObjectManager directly.
    -   **Cache Invalidation**: Both frontend blocks implement `IdentityInterface` and return appropriate cache tags from the Partner model. The Full Page Cache is invalidated when partners are created, updated, or deleted. Changes to a partner's active status also trigger cache invalidation for their specific partner view page.

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
    -   **Service Layer**: Created specialised services for specific business operations to improve separation of concerns.
    -   **Reduced Coupling**: Controllers and GraphQL resolvers use service classes rather than directly accessing repositories.
    -   **View Models**: View models are implemented for frontend templates, replacing the registry pattern for data sharing between controllers and blocks. This improves testability and follows Magento best practices.
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
-   Logo URL generation logic is centralised in the [`PartnerMediaUrlService`](Model/Service/PartnerMediaUrlService.php:0) to eliminate duplication.
-   The module implements a comprehensive caching strategy with two levels:
    -   **In-memory Repository Cache**: The `PartnerRepository` caches loaded entities within a single request and properly invalidates this cache when entities are saved or deleted.
    -   **Full Page Cache (FPC)**: The `Partner` model and frontend blocks implement `IdentityInterface` to ensure proper FPC invalidation when partners are modified.

- Throughout the codebase, are comprehensive PHPDoc comments, return type hints, and scalar type hints. Access modifiers are standardised, method signatures provide better type safety, and there's a custom search results implementation for type compatibility. Proper type casting and more specific exception types are added for better error handling.

- The module uses view models instead of the registry pattern for sharing data between controllers and templates. This follows Magento best practices and improves testability by:
  - Removing global state dependencies (registry)
  - Making dependencies explicit through constructor injection
  - Separating data provision logic from rendering logic
  - Simplifying the controller by removing registry-related code

This documentation provides a comprehensive overview of the `Wholesale_PartnerPortal` module. The module follows a service-orientated architecture with proper separation of concerns, making it maintainable, performant, and extensible.

## 13. Testing the Module

After making changes to the module, you should test it to ensure everything works correctly:

1. **Clear the Magento cache**:
   ```
   php bin/magento cache:clean
   ```

2. **Test the partner view page**:
   - Navigate to a partner view page (e.g., `wholesale/partners/view/slug/partner-slug`)
   - Verify that the partner details are displayed correctly
   - Check that the partner logo is displayed
   - Verify that all partner information (name, description, website, contact email) is shown correctly

3. **Test the partner list page**:
   - Navigate to the partner list page (`wholesale/partners/index`)
   - Verify that all active partners are listed
   - Check that clicking on a partner takes you to the correct partner view page

4. **Test with different partners**:
   - Test with partners that have different data (some with logos, some without, etc.)
   - Test with inactive partners (they should not be accessible)

5. **Test in different browsers**:
   - Verify that the pages display correctly in different browsers
   - Check that the responsive design works on different screen sizes




# TODO

### Media & Image Handling
- Implement a cron job to clean unused files from `/media/tmp/wholesale/partner/`, ideally deleting files older than 24 hours.
- Ensure consistent file naming during `moveLogoToPermanentStorage()` (e.g. lowercase, safe characters).
- Add support for duplicate file handling (e.g. hash or incremented filenames).
- Move fallback logo URL logic to a configurable value or service constant.
- Restrict allowed file types and sizes more strictly in `ImageUploader`.
- Add admin UI feedback for upload errors and validation failures.
- Add test coverage for image upload/delete behaviour, including edge cases.

### Value Objects & Domain Clarity
- Refactor primitive fields such as `slug`, `contact_email`, `logo`, and `website` into dedicated value objects (`PartnerSlug`, `EmailAddress`, `LogoImage`, `WebsiteUrl`) to encapsulate validation, formatting, and equality logic.
- Replace direct `string` usage in `PartnerInterface` setters and service layer with these value objects, ensuring conversion is handled internally for persistence.
- Update `PartnerDataSanitizerService` and `PartnerCommandService` to construct and validate these value objects during input processing.
- Ensure all entity comparisons and filtering logic (e.g. matching slugs or emails) use value object equality rather than raw strings.
- Extract `PartnerSlug`, `EmailAddress`, and `WebsiteUrl` into dedicated value objects to encapsulate validation and formatting, and enable isolated unit testing.

### Unit Testability & Refactoring
- Move logo-specific input logic (`processLogoData()`) out of `PartnerDataSanitizerService` into a dedicated service or helper to separate concerns and simplify tests.
- Ensure all services rely on explicit constructor-injected dependencies; wrap file operations and image handling in interfaces to enable mocking in tests.
- Replace raw associative arrays (e.g. form data) with well-structured DTOs or builder patterns for predictable test input construction.
- Add test-specific factories or builders to streamline generation of valid/invalid partner input data in test cases.
- Add unit tests for each value object to cover validation rules and comparison behaviour.
- Continue to encapsulate domain logic in `CommandService` and `QueryService` classes, avoiding behavioural code in models or repositories.

### Extension Attributes & API Readiness
- Add `etc/extension_attributes.xml` to declare attributes extending `PartnerInterface`.
- Implement `getExtensionAttributes()` and `setExtensionAttributes()` in the `Partner` entity model.
- Populate extension attributes in the repository layer to expose dynamic partner data.
- Optionally expose extension attributes via GraphQL or API response DTOs.


- Move media URL rendering from block methods into ViewModels using `PartnerMediaUrlService`.
- Consider exposing `PartnerQueryService` results to frontend JSON endpoints for use with JavaScript components or headless UIs.
- Aim to standardise all frontend partner data retrieval through services rather than direct model or repository access.

### Frontend Form Input Handling
- Ensure any future frontend forms (e.g. partner submissions or user-generated content) utilise `PartnerDataSanitizerService` for input cleaning.

### 📌 TODO: Expand Use of PartnerQueryService (CQRS Refactor)

- Refactor `partner_listing.xml` grid data source to use `PartnerQueryService::getList()` instead of direct collection access. This will centralise filtering and support future business rule changes.
- Update admin form ViewModel or block logic to use `PartnerQueryService::getById()` for retrieving partner data, replacing direct repository usage.
- Refactor `Block\PartnerList` and `Block\PartnerView` to use `PartnerQueryService` instead of direct repository or collection logic.
- Refactor `ViewModel\Partner` and `ViewModel\Adminhtml\PartnerView` to use `PartnerQueryService::getById()` or `getActiveBySlug()` where appropriate.
- Consider extracting pagination logic and filtering logic into `PartnerQueryService` to support richer frontend queries (e.g. featured only, search by keyword).
- Ensure all frontend-facing partner data access (controllers, blocks, view models) uses `PartnerQueryService` to enforce consistent visibility and sanitisation rules.
- Plan for future frontend JSON/REST endpoints to use `PartnerQueryService::getList()` and `getBySlug()` as their data source, not raw repositories.
- Add unit tests for all `PartnerQueryService` methods to independently verify filtering, visibility, and pagination logic.
- Consider removing the in-memory `$partnerCache` from the repository once all consumers use the query service, which can internally apply light caching if necessary.
- Review GraphQL resolvers and ensure they use only `PartnerQueryService` methods for data access.
