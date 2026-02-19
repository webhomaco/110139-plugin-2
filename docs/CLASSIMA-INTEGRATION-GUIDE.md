# Classima Theme Integration Guide

**Document Purpose:** Comprehensive guide for developers working on the Webhoma Subscription Plugin
**Last Updated:** 2026-02-19
**Classima Version:** 2.13.1
**RTCL Version:** Compatible with Classified Listing (RTCL) Pro

---

## Table of Contents

1. [Overview](#overview)
2. [Theme Architecture](#theme-architecture)
3. [RTCL Plugin Integration](#rtcl-plugin-integration)
4. [Phone Number System](#phone-number-system)
5. [Listing Templates](#listing-templates)
6. [Hooks Reference](#hooks-reference)
7. [Form System](#form-system)
8. [Metadata Reference](#metadata-reference)
9. [Integration Examples](#integration-examples)
10. [Best Practices](#best-practices)

---

## Overview

### What is Classima?

Classima is a WordPress theme by Persian Script designed specifically for classified listing websites. It works as a visual layer on top of the RTCL (Classified Listing) plugin, providing:

- Custom templates for listings, archives, and forms
- Phone number masking/revealing functionality
- Multiple layout styles for single listings and archives
- Seller information display blocks
- Search and filter interfaces
- Responsive design for mobile devices

### How This Plugin Interacts with Classima

The Webhoma Subscription Plugin integrates with Classima at several key points:

1. **Phone Number Reveal** - Replaces the default phone display with token-gated system
2. **Listing Forms** - Adds barter fields to listing submission forms
3. **Single Listing Display** - Shows barter information on listing pages
4. **Search Forms** - Adds barter tag filters to search interface
5. **Listing Cards** - Displays barter badges on listing thumbnails

---

## Theme Architecture

### Directory Structure

```
classima/
├── inc/                              # Core theme functionality
│   ├── constants.php                 # Theme version and constants
│   ├── helper.php                    # Global helper functions
│   ├── general.php                   # Theme setup, sidebars, support
│   ├── scripts.php                   # Script/CSS enqueuing
│   ├── rdtheme.php                   # Redux theme options
│   └── options/                      # Theme option panels
│
├── classified-listing/               # RTCL template overrides
│   ├── single-rtcl_listing.php       # Single listing main template
│   ├── archive-rtcl_listing.php      # Listing archive template
│   │
│   └── custom/                       # Custom Classima templates
│       ├── functions.php             # Main listing functions (977 lines)
│       ├── content-single.php        # Single listing style 1
│       ├── content-single-2.php      # Single listing style 2
│       ├── content-single-3.php      # Single listing style 3
│       ├── content-single-4.php      # Single listing style 4
│       ├── seller-info.php           # Seller information block
│       ├── sidebar-single.php        # Single listing sidebar
│       │
│       ├── list-items/               # Archive layouts
│       │   ├── archive-list-1.php through archive-list-8.php
│       │   └── archive-grid-1.php through archive-grid-9.php
│       │
│       └── listing-form/             # Form templates
│           ├── form.php              # Main form wrapper
│           ├── contact.php           # Contact fields (phone, email)
│           ├── category.php          # Category selection
│           ├── gallery.php           # Image upload
│           └── custom-field.php      # Custom fields
│
├── assets/                           # Theme static files
│   ├── css/                          # Stylesheets
│   ├── js/                           # JavaScript files
│   └── img/                          # Images
│
├── functions.php                     # Main theme bootstrap
├── header.php                        # Header template
└── footer.php                        # Footer template
```

### Key Theme Files

#### 1. `/inc/includes.php`
Central file inclusion dispatcher. Conditionally loads RTCL integration:

```php
if ( class_exists( 'RtclPro' ) ) {
    Helper::requires( 'custom/functions.php', 'classified-listing' );
}
```

#### 2. `/classified-listing/custom/functions.php`
**THE MOST IMPORTANT FILE** (977 lines)

Main class: `Listing_Functions`
- Singleton pattern: `Listing_Functions::instance()`
- Contains all listing-related helper methods
- Manages template rendering
- Controls phone number display
- Handles AJAX requests
- Integrates with RTCL hooks

---

## RTCL Plugin Integration

### What is RTCL?

RTCL (Classified Listing) is a plugin by RadiusTheme that provides:
- Custom post type: `rtcl_listing`
- Taxonomy: `rtcl_category`, `rtcl_location`
- Listing submission forms
- Search and filter functionality
- User account management
- Store functionality (with Pro version)

### RTCL Data Structure

**Post Type:** `rtcl_listing`
- Title: Listing title
- Content: Listing description
- Post meta: All listing details (phone, price, location, etc.)
- Taxonomies: Categories and locations
- Custom fields: User-defined fields

**Key RTCL Classes:**

```php
use Rtcl\Models\Listing;                           // Core listing model
use Rtcl\Helpers\Functions;                        // Helper functions
use Rtcl\Helpers\Link;                             // URL generation
use RtclPro\Helpers\Fns;                           // Pro helpers
use RtclPro\Controllers\Hooks\TemplateHooks;       // Template hooks
```

### RTCL Template Hierarchy

RTCL follows WordPress template hierarchy. Classima overrides templates by placing them in:

```
classima/classified-listing/[template-name].php
```

**Template Override Flow:**
1. Check child theme: `child-theme/classified-listing/`
2. Check parent theme: `classima/classified-listing/`
3. Fallback to plugin: `classified-listing/templates/`

---

## Phone Number System

### How Phone Numbers Work in Classima

#### Storage
Phone numbers are stored as post meta:
- **Key:** `phone`
- **Value:** Full phone number (e.g., "+1234567890")
- **WhatsApp Key:** `_rtcl_whatsapp_number`

#### Display Logic

**Location:** `/classified-listing/custom/functions.php` (lines 805-857)

**Method:** `Listing_Functions::the_phone( $phone, $whatsapp_number, $telegram )`

**Process:**
1. Get phone number from post meta or parameter
2. Apply masking based on settings
3. Generate HTML with data attributes
4. Output masked number with reveal button
5. JavaScript handles click to reveal

#### Masking System

**Default Behavior:**
- Masks last N digits of phone number
- Replacement character: 'XXX' (configurable)
- Stored in `data-options` JSON attribute

**Example:**
- Original: "+1234567890"
- Masked: "+1234XXX"
- Click reveals full number

**Filters Available:**

```php
// Change mask character
add_filter( 'rtcl_phone_number_placeholder', function( $placeholder ) {
    return '***'; // Changes XXX to ***
});

// Modify phone options array
add_filter( 'rtcl_phone_number_options', function( $options, $phone, $whatsapp, $telegram ) {
    // $options contains: mask_digit, mask_string, etc.
    return $options;
}, 10, 4 );
```

#### Phone Display Template

**Location:** `/classified-listing/custom/seller-info.php` (lines 107-109)

```php
<?php if ( $phone || $whatsapp ): ?>
    <div class="rtin-phone">
        <?php Listing_Functions::the_phone( $phone, $whatsapp ); ?>
    </div>
<?php endif; ?>
```

#### How Our Plugin Overrides Phone Display

**File:** `/functions/phone-reveal.php`

**Strategy:**
1. Remove default RTCL phone display hook
2. Add custom hook at same priority
3. Check if user has viewed listing
4. Show button if not viewed, full number if viewed
5. AJAX handler deducts tokens and marks as viewed

**Implementation:**

```php
// Remove default phone display
remove_action(
    'rtcl_single_listing_content_end',
    array( TemplateHooks::class, 'seller_phone_whatsapp_number' ),
    20
);

// Add custom phone display
add_action(
    'rtcl_single_listing_content_end',
    'wh_sub_custom_phone_display',
    20
);
```

---

## Listing Templates

### Single Listing Page

**Main Template:** `/classified-listing/single-rtcl_listing.php`

**Content Flow:**
```
1. Get listing object
2. Check listing exists and is published
3. Load header
4. Start main content area
5. Include content template (style 1-4)
6. Include sidebar (if enabled)
7. Load footer
```

**Four Style Variations:**

#### Style 1 (Default): `content-single.php`
```
├── Listing Header
│   ├── Breadcrumbs
│   ├── Category badges
│   ├── Title
│   ├── Meta info (date, views, location)
│   └── Social share
├── Image Gallery
├── Main Content
│   ├── Description
│   ├── Tabs (Details, Features)
│   ├── Custom fields
│   ├── Video
│   └── Map
├── Seller Information (sidebar component)
├── Business Hours
├── Related Listings
└── Comments
```

#### Styles 2-4
Similar structure with different layouts and element positioning.

### Sidebar Component: `sidebar-single.php`

**Responsive Behavior:**
- **Desktop:** Displayed in right/left sidebar
- **Mobile:** Moved below main content

**Contents:**
```php
├── Author/Store Info Card
│   ├── Avatar/Logo
│   ├── Name/Store name
│   ├── Member since
│   ├── Online status
│   └── Website link
├── Contact Information
│   ├── Location & Address
│   ├── Phone & WhatsApp    ← OUR PLUGIN HOOKS HERE
│   ├── Chat button
│   └── Email form button
├── Booking Form (if enabled)
└── Custom widgets
```

### Archive/Listing List Page

**Main Template:** `/classified-listing/archive-rtcl_listing.php`

**Layout Options:**
- List view (8 variations)
- Grid view (9 variations)
- Map view

**List Item Elements:**
```
├── Thumbnail image           ← Badge added here
├── Category badges
├── Listing type (Sale/Rent)
├── Featured badge
├── Title
├── Excerpt
├── Metadata (date, location, views)
├── Price
└── Action buttons
```

### How to Add Content to Templates

**Using Action Hooks:**

```php
// Before main listing content
add_action( 'classima_single_listing_before_contents', 'your_function' );

// After product info section
add_action( 'classima_single_listing_after_product', 'your_function' );

// After location display
add_action( 'classima_single_listing_after_location', 'your_function' );

// After related listings
add_action( 'classima_single_listing_after_related', 'your_function' );

// Mobile-specific info
add_action( 'classima_single_listing_mobile_info', 'your_function', 10, 1 );
```

---

## Hooks Reference

### Critical Hooks for This Plugin

#### 1. Listing Form Hooks

**Add Fields to Listing Form:**
```php
add_action( 'rtcl_listing_form', 'your_function', 25 );

function your_function() {
    // Add custom form fields here
    // Priority 25 puts fields at the bottom
}
```

**Save Form Data:**
```php
add_action( 'rtcl_listing_form_after_save_or_update', 'your_function', 10, 2 );

function your_function( $listing_id, $args ) {
    // $listing_id - The listing post ID
    // $args - Additional arguments
    // Save custom field data here
}
```

**Form Field Locations:**
- Priority 10: Top sections (title, category)
- Priority 15: Description
- Priority 20: Images, pricing, location
- Priority 25: Custom fields (OUR PLUGIN USES THIS)
- Priority 30: Submit button area

#### 2. Display Hooks

**Single Listing Content:**
```php
// At the top of listing content
add_action( 'rtcl_single_listing_content_start', 'your_function' );

// After main content
add_action( 'rtcl_single_listing_content_end', 'your_function', 15 );
// Priority 15 for barter display
// Priority 20 for phone display

// Seller info section
add_action( 'rtcl_listing_seller_information', 'your_function' );
```

**Archive/Grid View:**
```php
// Before listing card content
add_action( 'rtcl_after_listing_loop_thumbnail', 'your_function' );
// Used for badges

// Grid view specific
add_action( 'classima_grid_view_before_content', 'your_function' );
add_action( 'classima_grid_view_after_content', 'your_function' );

// List view specific
add_action( 'classima_listing_list_view_after_content', 'your_function' );
add_action( 'classima_list_view_after_content', 'your_function' );
```

**Seller Information Hooks:**
```php
// Author badges
add_action( 'rtcl_listing_author_badges', 'your_function', 10, 1 );

// Seller info card
add_action( 'rtcl_listing_seller_information', 'your_function' );
```

#### 3. Search/Filter Hooks

**Add to Search Form:**
```php
add_action( 'rtcl_widget_search_form', 'your_function', 20 );

function your_function() {
    // Add custom search fields
    // Priority 20 puts it after default fields
}
```

**Modify Listing Query:**
```php
add_filter( 'rtcl_listing_query_args', 'your_function' );

function your_function( $args ) {
    // Modify WP_Query args for listing archive
    // Add meta_query, tax_query, etc.
    return $args;
}
```

#### 4. Phone Number Hooks

**Change Mask Character:**
```php
add_filter( 'rtcl_phone_number_placeholder', 'your_function' );

function your_function( $placeholder ) {
    return 'XXX'; // or '***', '####', etc.
}
```

**Modify Phone Options:**
```php
add_filter( 'rtcl_phone_number_options', 'your_function', 10, 4 );

function your_function( $options, $phone, $whatsapp, $telegram ) {
    // $options contains:
    // - mask_digit: Number of digits to mask
    // - mask_string: Replacement string
    // - phone: Full phone number
    // - whatsapp: WhatsApp number
    // - telegram: Telegram handle

    return $options;
}
```

**Override Phone Display Completely:**
```php
// Remove default display
remove_action(
    'rtcl_single_listing_content_end',
    array( 'RtclPro\Controllers\Hooks\TemplateHooks', 'seller_phone_whatsapp_number' ),
    20
);

// Add custom display
add_action( 'rtcl_single_listing_content_end', 'your_custom_phone_display', 20 );
```

#### 5. Template Customization Hooks

**Classima-Specific Hooks:**
```php
// Header area
add_action( 'classima_header_top', 'your_function' );

// Single listing sections
add_action( 'classima_single_listing_before_contents', 'your_function' );
add_action( 'classima_single_listing_after_product', 'your_function' );
add_action( 'classima_single_listing_after_location', 'your_function' );
add_action( 'classima_single_listing_after_related', 'your_function' );
add_action( 'classima_single_listing_mobile_info', 'your_function', 10, 1 );
```

**Filter Hooks:**
```php
// Grid column classes
add_filter( 'rtcl_listings_grid_columns_class', 'your_function' );
add_filter( 'classima_listing_grid_col_class', 'your_function' );

// RTCL settings
add_filter( 'rtcl_style_settings', 'your_function' );
add_filter( 'rtcl_moderation_settings_options', 'your_function' );
add_filter( 'rtcl_archive_listing_settings_options', 'your_function' );

// Widget filter styling
add_filter( 'rtcl_widget_filter_fields', 'your_function' );
```

---

## Form System

### Listing Submission Form

**Location:** `/classified-listing/listing-form/`

**Main Form Template:** `form.php`

**Form Structure:**
```
1. Category selection (category.php)
2. Title field
3. Description editor
4. Pricing fields (pricing.php)
5. Gallery upload (gallery.php)
6. Contact information (contact.php)    ← PHONE FIELDS HERE
7. Location fields (location.php)
8. Custom fields (custom-field.php)
9. Submit button
```

### Contact Information Fields

**File:** `/classified-listing/listing-form/contact.php` (282 lines)

**Available Fields:**

#### Phone Number
```php
// Meta key: 'phone'
// Field type: text
// Required: Conditional (check via Functions::listingFormPhoneIsRequired())

<input
    type="text"
    name="phone"
    id="rtcl-phone"
    value="<?php echo esc_attr( $phone ); ?>"
    class="form-control"
    <?php echo Functions::listingFormPhoneIsRequired() ? 'required' : ''; ?>
/>
```

**Hooks:**
- Filter: `rtcl_verification_listing_form_phone_field`
- Action: `rtcl_listing_form_phone_warning`

#### WhatsApp Number
```php
// Meta key: '_rtcl_whatsapp_number'
// Field type: text
// Required: No
// Placeholder: "WhatsApp number with your country code. e.g.+1xxxxxxxxxx"

<input
    type="text"
    name="_rtcl_whatsapp_number"
    id="rtcl-whatsapp"
    value="<?php echo esc_attr( $whatsapp ); ?>"
    class="form-control"
/>
```

#### Email
```php
// Meta key: 'email'
// Required: Yes (if guest posting allowed)
```

#### Website
```php
// Meta key: 'website'
// Field type: url
```

#### Location Fields
```php
// Hierarchical: State → City → Town
// Or free-text address based on location type

// Address
// Meta key: 'address'

// Zipcode
// Meta key: 'zipcode'

// Geo Address (for geo-location type)
// Meta key: '_rtcl_geo_address'
```

#### Map
```php
// Latitude: 'latitude'
// Longitude: 'longitude'
// Hide map: 'hide_map' (checkbox)
```

### Adding Custom Fields to Form

**Method 1: Using rtcl_listing_form Hook**

```php
add_action( 'rtcl_listing_form', 'add_custom_fields', 25 );

function add_custom_fields() {
    global $post;

    // Get saved value
    $value = get_post_meta( $post->ID, '_custom_field', true );

    ?>
    <div class="rtcl-post-section">
        <h3 class="classified-listing-form-title">Custom Section</h3>
        <div class="form-group">
            <label for="custom_field">Custom Field</label>
            <input
                type="text"
                name="custom_field"
                id="custom_field"
                class="form-control"
                value="<?php echo esc_attr( $value ); ?>"
            />
        </div>
    </div>
    <?php
}
```

**Method 2: Using Form Builder (if enabled)**

```php
add_filter( 'rtcl_fb_custom_fields', 'add_form_builder_fields' );

function add_form_builder_fields( $fields ) {
    $fields[] = array(
        'name' => 'custom_field',
        'label' => 'Custom Field',
        'type' => 'text',
        'required' => false,
    );

    return $fields;
}
```

### Saving Custom Form Data

```php
add_action( 'rtcl_listing_form_after_save_or_update', 'save_custom_data', 10, 2 );

function save_custom_data( $listing_id, $args ) {
    // Sanitize input
    if ( isset( $_POST['custom_field'] ) ) {
        $value = sanitize_text_field( $_POST['custom_field'] );
        update_post_meta( $listing_id, '_custom_field', $value );
    }
}
```

### Form Validation

**Check Required Fields:**
```php
add_filter( 'rtcl_listing_form_required_fields', 'add_required_field' );

function add_required_field( $fields ) {
    $fields[] = 'custom_field';
    return $fields;
}
```

**Custom Validation:**
```php
add_filter( 'rtcl_listing_form_validate', 'validate_custom_field', 10, 2 );

function validate_custom_field( $valid, $listing_id ) {
    if ( empty( $_POST['custom_field'] ) ) {
        wp_die( 'Custom field is required.' );
    }

    return $valid;
}
```

---

## Metadata Reference

### Core Listing Meta Keys

**Contact Information:**
```php
'phone'                  // Phone number
'_rtcl_whatsapp_number' // WhatsApp number
'email'                 // Email address
'website'               // Website URL
```

**Location:**
```php
'address'               // Street address
'zipcode'              // Postal code
'_rtcl_geo_address'    // Geographic address (for geo-location)
'latitude'             // Map latitude
'longitude'            // Map longitude
'hide_map'             // Hide map (1 or 0)
```

**Listing Details:**
```php
'price'                // Listing price
'_price_type'          // Price type (fixed, negotiable, on_call)
'_rtcl_listing_type'   // Listing type (sell, rent)
'_views'               // View count
'_expiry_date'         // Listing expiration date
'never_expires'        // Never expire flag (1 or 0)
'featured'             // Featured listing (1 or 0)
'_top'                 // Top/bumped listing (1 or 0)
```

**Media:**
```php
'_rtcl_video_urls'     // Video URLs (serialized array)
'_thumbnail_id'        // Featured image ID
'_gallery'             // Image gallery IDs (serialized array)
```

**Business Information:**
```php
'_rtcl_business_hours' // Business hours (serialized array)
'_rtcl_247_hours'      // 24/7 hours flag (yes/no)
```

**Advanced:**
```php
'_rtcl_manager_id'     // Manager ID (for managed listings)
'classima_spec_info'   // Specification/Features info
'_rtcl_custom_fields'  // Custom field values
```

### Taxonomies

**Categories:**
```php
Taxonomy: 'rtcl_category'
Hierarchical: Yes
Used for: Listing categories
```

**Locations:**
```php
Taxonomy: 'rtcl_location'
Hierarchical: Yes
Used for: Geographic locations
```

**Listing Types (Custom):**
```php
Meta key: '_rtcl_listing_type'
Values: 'sell', 'rent', 'exchange'
```

### Getting Metadata

**Using RTCL Functions:**
```php
use Rtcl\Helpers\Functions;

// Get phone number
$phone = Functions::get_listing_contact_numbers( $listing_id );

// Get email
$email = get_post_meta( $listing_id, 'email', true );

// Get price
$price = get_post_meta( $listing_id, 'price', true );

// Get views
$views = absint( get_post_meta( $listing_id, '_views', true ) );

// Check if featured
$is_featured = get_post_meta( $listing_id, 'featured', true );
```

**Using Listing Model:**
```php
use Rtcl\Models\Listing;

$listing = new Listing( $listing_id );

// Get phone
$phone = $listing->get_phone();

// Get email
$email = $listing->get_email();

// Get address
$address = $listing->get_address();

// Get location terms
$locations = $listing->get_locations();

// Get categories
$categories = $listing->get_categories();

// Check if featured
$is_featured = $listing->is_featured();
```

---

## Integration Examples

### Example 1: Override Phone Display with Token System

**Our Implementation:** `/functions/phone-reveal.php`

```php
/**
 * Remove default phone display and add custom token-gated version
 */
function wh_sub_setup_phone_override() {
    // Remove RTCL default phone display
    remove_action(
        'rtcl_single_listing_content_end',
        array( 'RtclPro\Controllers\Hooks\TemplateHooks', 'seller_phone_whatsapp_number' ),
        20
    );

    // Add our custom phone display
    add_action(
        'rtcl_single_listing_content_end',
        'wh_sub_custom_phone_display',
        20
    );
}
add_action( 'init', 'wh_sub_setup_phone_override' );

/**
 * Display phone with token gate
 */
function wh_sub_custom_phone_display() {
    // Skip if not single listing
    if ( ! is_singular( 'rtcl_listing' ) ) {
        return;
    }

    // Skip if user not logged in
    if ( ! is_user_logged_in() ) {
        return;
    }

    global $post;
    $listing_id = $post->ID;
    $user_id = get_current_user_id();

    // Skip if listing author (show their own phone)
    if ( $post->post_author == $user_id ) {
        // Show default phone display
        return;
    }

    // Get phone number
    $phone = get_post_meta( $listing_id, 'phone', true );
    if ( empty( $phone ) ) {
        return;
    }

    // Check if already viewed
    $has_viewed = wh_sub_has_viewed_listing( $user_id, $listing_id );

    ?>
    <div class="wh-phone-reveal-container">
        <?php if ( $has_viewed ): ?>
            <!-- Already viewed - show phone directly -->
            <div class="wh-phone-number">
                <i class="icon-phone"></i>
                <a href="tel:<?php echo esc_attr( $phone ); ?>">
                    <?php echo esc_html( $phone ); ?>
                </a>
            </div>
        <?php else: ?>
            <!-- Not viewed - show reveal button -->
            <button
                class="wh-reveal-phone-btn"
                data-listing-id="<?php echo esc_attr( $listing_id ); ?>"
                data-nonce="<?php echo wp_create_nonce( 'wh_sub_nonce' ); ?>"
            >
                <i class="icon-lock"></i>
                Reveal Phone (1 Token)
            </button>
            <div class="wh-phone-number" style="display:none;"></div>
        <?php endif; ?>
    </div>
    <?php
}
```

### Example 2: Add Custom Fields to Listing Form

**Our Implementation:** `/functions/barter.php`

```php
/**
 * Add barter fields to listing form
 */
function wh_sub_add_form_fields() {
    global $post;

    // Get saved data
    $data = null;
    if ( $post && $post->ID ) {
        global $wpdb;
        $table = $wpdb->prefix . 'barter_data';
        $data = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM $table WHERE listing_id = %d",
            $post->ID
        ));
    }

    $description = $data ? $data->description : '';
    $tags = $data && $data->tags ? json_decode( $data->tags, true ) : array();

    ?>
    <div class="rtcl-post-section wh-barter-section">
        <h3 class="classified-listing-form-title">
            <?php esc_html_e( 'Trade Option (Barter)', 'webhoma-subscription' ); ?>
        </h3>

        <div class="form-group">
            <label for="wh_barter_description">
                <?php esc_html_e( 'What are you willing to trade for?', 'webhoma-subscription' ); ?>
            </label>
            <textarea
                name="wh_barter_description"
                id="wh_barter_description"
                class="form-control"
                rows="3"
                placeholder="<?php esc_attr_e( 'Example: Laptop, Smartphone, Camera', 'webhoma-subscription' ); ?>"
            ><?php echo esc_textarea( $description ); ?></textarea>
        </div>

        <div class="form-group">
            <label for="wh_barter_tag_input">
                <?php esc_html_e( 'Trade Tags', 'webhoma-subscription' ); ?>
            </label>
            <input
                type="text"
                id="wh_barter_tag_input"
                class="form-control"
                placeholder="<?php esc_attr_e( 'Type and press Enter', 'webhoma-subscription' ); ?>"
                autocomplete="off"
            />
            <div id="wh-tag-suggestions" class="wh-tag-suggestions"></div>
            <div id="wh-barter-tags" class="wh-tag-container">
                <?php if ( ! empty( $tags ) ): ?>
                    <?php foreach ( $tags as $tag ): ?>
                        <span class="wh-tag">
                            <?php echo esc_html( $tag ); ?>
                            <button type="button" class="wh-tag-remove">&times;</button>
                            <input type="hidden" name="wh_barter_tags[]" value="<?php echo esc_attr( $tag ); ?>">
                        </span>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
}
add_action( 'rtcl_listing_form', 'wh_sub_add_form_fields', 25 );
```

### Example 3: Save Custom Form Data

**Our Implementation:** `/functions/barter.php`

```php
/**
 * Save barter data when listing is saved
 */
function wh_sub_save_data( $listing_id, $args ) {
    global $wpdb;

    $table = $wpdb->prefix . 'barter_data';

    // Get form data
    $description = isset( $_POST['wh_barter_description'] )
        ? sanitize_textarea_field( $_POST['wh_barter_description'] )
        : '';

    $tags = isset( $_POST['wh_barter_tags'] ) && is_array( $_POST['wh_barter_tags'] )
        ? array_map( 'sanitize_text_field', $_POST['wh_barter_tags'] )
        : array();

    // Check if data exists
    $exists = $wpdb->get_var( $wpdb->prepare(
        "SELECT id FROM $table WHERE listing_id = %d",
        $listing_id
    ));

    if ( $exists ) {
        // Update existing
        $wpdb->update(
            $table,
            array(
                'description' => $description,
                'tags' => json_encode( $tags, JSON_UNESCAPED_UNICODE ),
            ),
            array( 'listing_id' => $listing_id ),
            array( '%s', '%s' ),
            array( '%d' )
        );
    } else {
        // Insert new
        $wpdb->insert(
            $table,
            array(
                'listing_id' => $listing_id,
                'description' => $description,
                'tags' => json_encode( $tags, JSON_UNESCAPED_UNICODE ),
            ),
            array( '%d', '%s', '%s' )
        );
    }
}
add_action( 'rtcl_listing_form_after_save_or_update', 'wh_sub_save_data', 10, 2 );
```

### Example 4: Display Custom Data on Single Listing

**Our Implementation:** `/functions/barter.php`

```php
/**
 * Display barter info on single listing
 */
function wh_sub_display_info() {
    if ( ! is_singular( 'rtcl_listing' ) ) {
        return;
    }

    global $post, $wpdb;

    $table = $wpdb->prefix . 'barter_data';
    $data = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM $table WHERE listing_id = %d",
        $post->ID
    ));

    if ( ! $data || ( empty( $data->description ) && empty( $data->tags ) ) ) {
        return;
    }

    $tags = json_decode( $data->tags, true );

    ?>
    <div class="wh-barter-info site-content-block">
        <div class="main-title-block">
            <h3 class="title"><?php esc_html_e( 'Trade/Barter Available', 'webhoma-subscription' ); ?></h3>
        </div>

        <?php if ( ! empty( $data->description ) ): ?>
            <div class="wh-barter-description">
                <p><?php echo esc_html( $data->description ); ?></p>
            </div>
        <?php endif; ?>

        <?php if ( ! empty( $tags ) && is_array( $tags ) ): ?>
            <div class="wh-barter-tags">
                <strong><?php esc_html_e( 'Looking for:', 'webhoma-subscription' ); ?></strong>
                <div class="wh-tag-list">
                    <?php foreach ( $tags as $tag ): ?>
                        <span class="wh-tag-badge"><?php echo esc_html( $tag ); ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <?php
}
add_action( 'rtcl_single_listing_content_end', 'wh_sub_display_info', 15 );
```

### Example 5: Add Search Filter

**Our Implementation:** `/functions/barter.php`

```php
/**
 * Add barter tag filter to search form
 */
function wh_sub_search_filter() {
    ?>
    <div class="form-group wh-barter-search">
        <label for="wh_search_tags">
            <?php esc_html_e( 'Trade Tags', 'webhoma-subscription' ); ?>
        </label>
        <input
            type="text"
            id="wh_search_tags"
            name="wh_search_tags"
            class="form-control"
            placeholder="<?php esc_attr_e( 'Search by trade tags', 'webhoma-subscription' ); ?>"
            autocomplete="off"
        />
        <div id="wh-search-tag-suggestions" class="wh-tag-suggestions"></div>
        <div id="wh-search-selected-tags" class="wh-tag-container"></div>
    </div>
    <?php
}
add_action( 'rtcl_widget_search_form', 'wh_sub_search_filter', 20 );
```

### Example 6: Modify Listing Query

**Our Implementation:** `/functions/barter.php`

```php
/**
 * Filter listings by barter tags
 */
function wh_sub_filter_query( $args ) {
    // Check if barter tag search is present
    if ( ! isset( $_GET['wh_search_tags'] ) || empty( $_GET['wh_search_tags'] ) ) {
        return $args;
    }

    $search_tags = sanitize_text_field( $_GET['wh_search_tags'] );
    $tags = array_map( 'trim', explode( ',', $search_tags ) );

    if ( empty( $tags ) ) {
        return $args;
    }

    global $wpdb;
    $table = $wpdb->prefix . 'barter_data';

    // Build LIKE conditions for each tag
    $conditions = array();
    foreach ( $tags as $tag ) {
        $conditions[] = $wpdb->prepare(
            "tags LIKE %s",
            '%' . $wpdb->esc_like( $tag ) . '%'
        );
    }

    $where = implode( ' OR ', $conditions );

    // Get listing IDs that match
    $listing_ids = $wpdb->get_col(
        "SELECT listing_id FROM $table WHERE $where"
    );

    if ( empty( $listing_ids ) ) {
        // No matches - return empty result
        $args['post__in'] = array( 0 );
    } else {
        // Filter by matching IDs
        $args['post__in'] = $listing_ids;
    }

    return $args;
}
add_filter( 'rtcl_listing_query_args', 'wh_sub_filter_query' );
```

### Example 7: Add Badge to Listing Card

**Our Implementation:** `/functions/barter.php`

```php
/**
 * Add barter badge to listing thumbnail
 */
function wh_sub_add_badge() {
    global $post, $wpdb;

    $table = $wpdb->prefix . 'barter_data';
    $has_barter = $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM $table WHERE listing_id = %d AND (description != '' OR tags != '')",
        $post->ID
    ));

    if ( ! $has_barter ) {
        return;
    }

    ?>
    <div class="wh-barter-badge">
        <span class="badge badge-success">
            <?php esc_html_e( 'Trade Available', 'webhoma-subscription' ); ?>
        </span>
    </div>
    <?php
}
add_action( 'rtcl_after_listing_loop_thumbnail', 'wh_sub_add_badge' );
```

---

## Best Practices

### 1. Hook Priority Management

**Understanding Priority:**
- Lower number = Earlier execution
- Default priority: 10
- Use unique priorities to control order

**Recommended Priorities:**
```php
// Form fields
add_action( 'rtcl_listing_form', 'function', 25 );  // After core fields

// Display on single listing
add_action( 'rtcl_single_listing_content_end', 'function', 15 );  // Before phone
add_action( 'rtcl_single_listing_content_end', 'function', 20 );  // Phone display

// Badges on thumbnails
add_action( 'rtcl_after_listing_loop_thumbnail', 'function', 10 );  // Default
```

### 2. Data Sanitization

**Always sanitize inputs:**

```php
// Text fields
$value = sanitize_text_field( $_POST['field'] );

// Textarea
$value = sanitize_textarea_field( $_POST['field'] );

// Email
$value = sanitize_email( $_POST['field'] );

// URL
$value = esc_url_raw( $_POST['field'] );

// Numbers
$value = absint( $_POST['field'] );        // Positive integer
$value = intval( $_POST['field'] );        // Any integer
$value = floatval( $_POST['field'] );      // Float

// Arrays
$values = array_map( 'sanitize_text_field', $_POST['fields'] );
```

### 3. Output Escaping

**Always escape outputs:**

```php
// HTML content
echo esc_html( $text );

// Attributes
echo '<input value="' . esc_attr( $value ) . '">';

// URLs
echo '<a href="' . esc_url( $url ) . '">Link</a>';

// JavaScript
echo '<script>var data = ' . wp_json_encode( $data ) . ';</script>';

// Textarea
echo '<textarea>' . esc_textarea( $value ) . '</textarea>';
```

### 4. Database Queries

**Use prepared statements:**

```php
global $wpdb;

// SELECT
$results = $wpdb->get_results( $wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}table WHERE id = %d AND name = %s",
    $id,
    $name
));

// INSERT
$wpdb->insert(
    $wpdb->prefix . 'table',
    array(
        'column1' => $value1,
        'column2' => $value2,
    ),
    array( '%s', '%d' )  // Format: %s = string, %d = integer, %f = float
);

// UPDATE
$wpdb->update(
    $wpdb->prefix . 'table',
    array( 'column' => $new_value ),      // Data to update
    array( 'id' => $id ),                 // Where clause
    array( '%s' ),                        // Data format
    array( '%d' )                         // Where format
);

// DELETE
$wpdb->delete(
    $wpdb->prefix . 'table',
    array( 'id' => $id ),
    array( '%d' )
);
```

### 5. AJAX Security

**Nonce verification:**

```php
// Generate nonce
wp_localize_script( 'script-handle', 'ajaxData', array(
    'ajax_url' => admin_url( 'admin-ajax.php' ),
    'nonce' => wp_create_nonce( 'action_name_nonce' )
));

// Verify nonce in AJAX handler
add_action( 'wp_ajax_action_name', 'ajax_handler' );

function ajax_handler() {
    check_ajax_referer( 'action_name_nonce', 'nonce' );

    // Process request

    wp_send_json_success( $data );
}
```

### 6. Checking Permissions

**User capability checks:**

```php
// Check if user is logged in
if ( ! is_user_logged_in() ) {
    return;
}

// Check if user is listing author
if ( get_current_user_id() != $post->post_author ) {
    return;
}

// Check user capability
if ( ! current_user_can( 'edit_posts' ) ) {
    wp_die( 'Permission denied' );
}

// Using RTCL helper
use RtclPro\Helpers\Fns;

if ( Fns::registered_user_only( 'listing_seller_information' ) ) {
    // Show only to registered users
}
```

### 7. Conditional Display

**Check page context:**

```php
// Single listing page
if ( is_singular( 'rtcl_listing' ) ) {
    // Single listing code
}

// Listing archive
if ( is_post_type_archive( 'rtcl_listing' ) ) {
    // Archive code
}

// Category page
if ( is_tax( 'rtcl_category' ) ) {
    // Category code
}

// Any RTCL page
if ( is_singular( 'rtcl_listing' ) || is_post_type_archive( 'rtcl_listing' ) || is_tax( 'rtcl_category' ) ) {
    // RTCL page code
}
```

### 8. Asset Enqueuing

**Conditional loading:**

```php
function enqueue_assets() {
    // Only on RTCL pages
    if ( ! is_singular( 'rtcl_listing' ) && ! is_post_type_archive( 'rtcl_listing' ) ) {
        return;
    }

    // Enqueue CSS
    wp_enqueue_style(
        'plugin-style',
        PLUGIN_URL . 'assets/css/style.css',
        array(),  // Dependencies
        PLUGIN_VERSION
    );

    // Enqueue JS
    wp_enqueue_script(
        'plugin-script',
        PLUGIN_URL . 'assets/js/script.js',
        array( 'jquery' ),  // Dependencies
        PLUGIN_VERSION,
        true  // In footer
    );

    // Localize script with data
    wp_localize_script( 'plugin-script', 'pluginData', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce' => wp_create_nonce( 'plugin_nonce' ),
        'messages' => array(
            'success' => __( 'Success!', 'text-domain' ),
            'error' => __( 'Error occurred', 'text-domain' ),
        )
    ));
}
add_action( 'wp_enqueue_scripts', 'enqueue_assets' );
```

### 9. Internationalization

**Make strings translatable:**

```php
// Simple text
__( 'Text', 'text-domain' );

// Echo text
_e( 'Text', 'text-domain' );

// With HTML escaping
esc_html__( 'Text', 'text-domain' );
esc_html_e( 'Text', 'text-domain' );

// With attribute escaping
esc_attr__( 'Text', 'text-domain' );
esc_attr_e( 'Text', 'text-domain' );

// Pluralization
_n( 'Singular', 'Plural', $count, 'text-domain' );

// Context-specific
_x( 'Text', 'context', 'text-domain' );
```

### 10. Error Handling

**Graceful degradation:**

```php
// Check if class exists
if ( ! class_exists( 'RtclPro' ) ) {
    return; // Or show admin notice
}

// Check if function exists
if ( ! function_exists( 'rtcl_function' ) ) {
    return;
}

// Check post exists
if ( ! $post || ! isset( $post->ID ) ) {
    return;
}

// Check metadata exists
$value = get_post_meta( $post->ID, 'key', true );
if ( empty( $value ) ) {
    return;
}

// Database query error checking
global $wpdb;
$result = $wpdb->get_row( $query );

if ( $wpdb->last_error ) {
    error_log( 'Database error: ' . $wpdb->last_error );
    return false;
}
```

---

## Troubleshooting Common Issues

### Issue 1: Form Fields Not Showing

**Possible Causes:**
1. Form Builder enabled (uses different hooks)
2. Wrong hook priority
3. Theme cached

**Solutions:**
```php
// Check if Form Builder is active
if ( class_exists( 'Rtcl\Services\FormBuilder\FBHelper' ) ) {
    // Use Form Builder hooks instead
}

// Clear template cache
// Go to: WordPress Admin → Classified Listing → Settings → Advanced → Clear Cache
```

### Issue 2: Custom Data Not Saving

**Possible Causes:**
1. Missing sanitization
2. Wrong hook
3. Incorrect meta key

**Debug:**
```php
add_action( 'rtcl_listing_form_after_save_or_update', 'debug_save', 10, 2 );

function debug_save( $listing_id, $args ) {
    error_log( 'Listing ID: ' . $listing_id );
    error_log( 'POST data: ' . print_r( $_POST, true ) );
}
```

### Issue 3: Hooks Not Firing

**Possible Causes:**
1. Hook name typo
2. Plugin loaded too early/late
3. RTCL Pro not active

**Check:**
```php
// Add debug logging
add_action( 'hook_name', function() {
    error_log( 'Hook fired!' );
}, 1 );  // Priority 1 to fire first

// Check if RTCL is active
if ( ! class_exists( 'RtclPro' ) ) {
    add_action( 'admin_notices', function() {
        echo '<div class="error"><p>RTCL Pro required!</p></div>';
    });
}
```

### Issue 4: Phone Number Not Displaying

**Possible Causes:**
1. Phone meta key incorrect
2. Permission check blocking display
3. Hook override conflict

**Debug:**
```php
// Check phone value
global $post;
$phone = get_post_meta( $post->ID, 'phone', true );
error_log( 'Phone: ' . $phone );

// Check if hook is attached
global $wp_filter;
error_log( print_r( $wp_filter['rtcl_single_listing_content_end'], true ) );
```

### Issue 5: AJAX Request Failing

**Possible Causes:**
1. Nonce verification failing
2. User not logged in
3. Action not registered

**Debug:**
```php
// Check browser console for errors
// Check WordPress debug log

// Add more detailed error messages
function ajax_handler() {
    if ( ! check_ajax_referer( 'nonce_name', 'nonce', false ) ) {
        wp_send_json_error( array(
            'message' => 'Security check failed',
            'nonce_sent' => $_POST['nonce'],
        ));
    }

    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array(
            'message' => 'User not logged in'
        ));
    }

    // Continue processing
}
```

---

## Additional Resources

### Official Documentation

- **RTCL Documentation:** https://www.radiustheme.com/docs/classified-listing/
- **Classima Documentation:** https://www.radiustheme.com/docs/classima/
- **WordPress Codex:** https://codex.wordpress.org/
- **WordPress Hooks Database:** https://adambrown.info/p/wp_hooks

### Useful RTCL Functions

```php
use Rtcl\Helpers\Functions;

// Check if field is disabled
Functions::is_field_disabled( 'phone' );

// Get option
Functions::get_option( 'option_name' );

// Get option item
Functions::get_option_item( 'group', 'item', 'default', 'type' );

// Check if phone required
Functions::listingFormPhoneIsRequired();

// Get listing contact numbers
Functions::get_listing_contact_numbers( $listing_id );
```

### Classima Helper Functions

```php
use radiustheme\Classima\Helper;

// Get template part
Helper::get_template_part( 'template-name', $args );

// Get custom listing template
Listing_Functions::get_custom_listing_template( 'template-name', true, $args );
```

---

## Conclusion

This guide covers the essential integration points between the Webhoma Subscription Plugin and the Classima theme. Key takeaways:

1. **Classima** is a template layer over RTCL plugin
2. **Main integration file:** `/classified-listing/custom/functions.php`
3. **Phone system** uses masking with reveal functionality
4. **Hooks** are the primary integration method
5. **Forms** use `rtcl_listing_form` action hook
6. **Display** uses various `rtcl_*` and `classima_*` hooks
7. **Security** requires sanitization, escaping, and nonce verification

For new features, always:
- Check existing hooks first
- Follow WordPress coding standards
- Sanitize inputs and escape outputs
- Use prepared statements for database queries
- Test on different listing styles and layouts
- Verify mobile responsiveness

**Next Steps:**
- Read `TOKEN-SYSTEM-README.md` for token implementation details
- Review `project-documentation.md` for project overview
- Examine plugin source code in `/functions/` and `/ajax/` directories
- Test integrations with different Classima theme settings

---

**Document Version:** 1.0
**Author:** Webhoma Development Team
**Contact:** https://webhoma.ir
