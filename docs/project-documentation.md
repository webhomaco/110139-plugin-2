# Project Documentation: Classima VIP Plugin

**Plugin Name:** Classima VIP Plugin
**Version:** 1.07
**Last Updated:** 2026-02-19
**Current Status:** Barter System Complete with Advanced Search Features
**Technology Stack:** WordPress, Classified Listing (RTCL) Plugin, Classima Theme

---

## Table of Contents

1. [Project Overview](#project-overview)
2. [System Features](#system-features)
3. [Database Schema](#database-schema)
4. [Code Architecture](#code-architecture)
5. [Barter System Implementation](#barter-system-implementation)
6. [Search & Filter System](#search--filter-system)
7. [Frontend Integration](#frontend-integration)
8. [Technical Decisions](#technical-decisions)
9. [File Structure](#file-structure)
10. [Configuration](#configuration)

---

## Project Overview

### Plugin Purpose
The Classima VIP Plugin adds premium features to WordPress classified ads websites using the Classima theme and RTCL (Classified Listing) plugin. Currently implements a complete barter/trade system with advanced search capabilities.

### Main Features
1. **Barter/Trade System** - Sellers indicate willingness to trade items with tag-based matching
2. **Advanced Search** - Multi-tag autocomplete search with intelligent filtering
3. **Visual Indicators** - "Trade Available" badges on listing cards
4. **Clickable Tags** - Tags link to search results for that specific trade item
5. **Clean UI** - Modern, responsive interface matching Classima theme design

---

## System Features

### 1. Barter System (تهاتر - Exchange/Trade)

#### Listing Submission
Sellers can add trade information when creating/editing listings:
- **Description**: Text field describing what they want in exchange
- **Tags**: Multiple tags indicating desired items (e.g., "smartphone", "laptop", "camera")
- **Tag Autocomplete**: Real-time suggestions as user types
- **Multi-select**: Can add multiple trade tags to one listing

#### Listing Display
- **Trade Description Section**: Shows on single listing pages with description and tags
- **Styled Tags**: Pink/red tags with hover effects matching frontend design
- **Clickable Tags**: Each tag links to search results for that trade term

#### Visual Indicators
- **"Trade Available" Badge**: Green badge on listing cards
- **Positioned on thumbnails**: Clearly visible in listing grids/lists

### 2. Search & Filter System

#### Banner Search Integration
- **Trade Tags Input**: Added to main banner search form
- **Autocomplete Dropdown**: Suggestions appear as user types (minimum 2 characters)
- **Multi-Select**: Select multiple tags from suggestions only
- **Selected Tags Display**: Clean row below search inputs showing "Selected tags: [tag1] [tag2]"
- **Remove Tags**: Click × to remove individual tags
- **Filtered Suggestions**: Already-selected tags don't appear in suggestions

#### Search Results
- **Accurate Filtering**: Only shows listings with selected barter tags
- **Query Optimization**: Uses `pre_get_posts` hook at priority 999
- **Multiple Forms**: Works with both desktop and mobile search forms
- **URL Parameters**: Tags passed as `barter_tags[]=tag1&barter_tags[]=tag2`

#### Technical Features
- **Event Delegation**: JavaScript handles dynamically injected forms
- **Unique IDs**: Each form instance gets unique field IDs to prevent conflicts
- **Hidden Inputs**: Form submission inputs kept separate from visual display
- **Synchronized State**: Visual tags and form data stay in sync

---

## Database Schema

### wp_barter_data

```sql
CREATE TABLE wp_barter_data (
    id bigint(20) NOT NULL AUTO_INCREMENT,
    listing_id bigint(20) NOT NULL,
    description text,
    tags text,  -- JSON array of tag strings
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY listing_id (listing_id)
);
```

**Fields:**
- `id`: Auto-increment primary key
- `listing_id`: References `wp_posts.ID` (post_type = 'rtcl_listing')
- `description`: Text description of what seller wants to trade for
- `tags`: JSON array, e.g., `["smartphone", "laptop", "camera"]`
- `created_at`: Timestamp of record creation

**Indexes:**
- Primary key on `id`
- Index on `listing_id` for fast lookups

---

## Code Architecture

### Plugin Structure

```
110139-plugin-2/
├── webhoma-subscription.php         # Main plugin file (hooks, initialization)
├── install.php                      # Database table creation
├── functions/
│   ├── barter.php                   # Barter functions (~500 lines)
│   ├── tokens.php                   # Token system functions
│   └── phone-reveal.php             # Phone reveal functionality
├── ajax/
│   ├── barter-ajax.php              # Tag autocomplete AJAX handler
│   └── phone-ajax.php               # Phone reveal AJAX
├── assets/
│   ├── css/
│   │   ├── barter.css               # Barter styling (~220 lines)
│   │   └── phone-reveal.css         # Phone reveal styles
│   └── js/
│       ├── barter.js                # Tag management & search (~400 lines)
│       └── phone-reveal.js          # Phone reveal logic
├── admin-helper.php                 # Admin utilities
└── README.md                        # Plugin readme
```

### Coding Standards

**WordPress Best Practices:**
- Direct `$wpdb` usage with prepared statements
- Proper sanitization: `sanitize_text_field()`, `sanitize_textarea_field()`, `wp_unslash()`
- Proper escaping: `esc_html()`, `esc_attr()`, `esc_url()`
- Nonce verification: `check_ajax_referer()`
- Internationalization ready: `__()`, `esc_html__()`

**Example Pattern:**
```php
function wh_sub_function_name($param1, $param2) {
    global $wpdb;

    // Sanitize inputs
    $param1 = absint($param1);
    $param2 = sanitize_text_field($param2);

    // Prepared statement
    $result = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}table_name WHERE id = %d AND name = %s",
        $param1,
        $param2
    ));

    return $result;
}
```

---

## Barter System Implementation

### Core Functions (functions/barter.php)

#### Data Management
```php
// Save barter data when listing is saved
wh_sub_save_barter_data($listing_id)

// Get barter data for a listing
wh_sub_get_data($listing_id)

// Get all unique tags from database
wh_sub_get_all_tags($search = '')

// Delete barter data when listing is deleted
wh_sub_delete_data($listing_id)
```

#### Display Functions
```php
// Add form fields to listing submission
wh_sub_add_fields($listing)

// Display barter info on single listing page
wh_sub_display_info($listing)

// Add "Trade Available" badge to listing cards
wh_sub_add_badge()

// Add search filter to banner search
wh_sub_search_filter()
```

#### Query Filtering
```php
// Modify WordPress query to filter by barter tags
wh_sub_filter_query($query)
// - Checks GET parameters for barter_tags[]
// - Queries database for matching listings
// - Modifies post__in to show only results
// - Priority 999 to run after all RTCL filters
```

### AJAX Handlers (ajax/barter-ajax.php)

```php
// Search for tags matching user input
wh_sub_ajax_search_tags()
// - Receives search term via POST
// - Returns JSON with matching tags array
// - Limited to 10 suggestions
// - Available to logged-in and non-logged-in users
```

### Hooks Used

**Form Hooks:**
- `rtcl_listing_form` (priority 25) - Add barter fields to listing form
- `rtcl_listing_form_after_save_or_update` - Save barter data

**Display Hooks:**
- `rtcl_single_listing_content_end` (priority 15) - Display barter info
- `rtcl_after_listing_loop_thumbnail` - Add "Trade Available" badge

**Search Hooks:**
- `rtcl_widget_search_inline_form` (priority 20) - Add filter to inline search
- `rtcl_widget_search_vertical_form` (priority 20) - Add filter to vertical search

**Query Hooks:**
- `pre_get_posts` (priority 999) - Filter listings by barter tags

**Asset Hooks:**
- `wp_enqueue_scripts` - Load CSS and JavaScript
- `wp_localize_script` - Pass AJAX URL and nonce to JavaScript

---

## Search & Filter System

### Frontend Implementation

#### HTML Structure
```html
<!-- Banner Search Form -->
<form class="classima-listing-search-form">
    <!-- Location, Category, Keyword, Type fields... -->

    <!-- Barter Tags Field (injected via JavaScript) -->
    <div class="rtin-barter-space">
        <div class="rtcl-search-input-button rtin-barter">
            <input type="text" class="wh-barter-search-input"
                   placeholder="Trade Tags..." />
        </div>
        <div class="wh-tag-suggestions"></div>
    </div>

    <!-- Hidden inputs for form submission -->
    <div class="wh-barter-hidden-inputs" style="display:none;">
        <input type="hidden" name="barter_tags[]" value="tag1" />
        <input type="hidden" name="barter_tags[]" value="tag2" />
    </div>

    <button type="submit">Search</button>
</form>

<!-- Selected Tags Display (below form) -->
<div class="wh-selected-tags-row">
    <span class="wh-selected-tags-label">Selected tags:</span>
    <div class="wh-selected-tags">
        <span class="wh-selected-tag">
            tag1
            <span class="wh-remove-search-tag">×</span>
        </span>
    </div>
</div>
```

#### JavaScript Logic (assets/js/barter.js)

**Field Injection:**
- Detects `.classima-listing-search-form` elements
- Injects barter input field before search button
- Creates unique IDs for each form instance (mobile + desktop)
- Loads selected tags from URL parameters on page load

**Autocomplete:**
- Triggers AJAX on input (minimum 2 characters)
- Debounces requests (300ms delay)
- Shows "Loading..." indicator during fetch
- Displays suggestions in dropdown
- Filters out already-selected tags from results

**Tag Management:**
- Tags can ONLY be added by clicking suggestions (no manual entry)
- Enter key prevented from adding arbitrary text
- Click on suggestion adds tag to visual display AND hidden inputs
- Click × removes tag from both visual and hidden inputs
- Dropdown hides after selection
- Input refocuses after tag added

**Dual Storage:**
- Visual tags displayed below search form (for user clarity)
- Hidden inputs stored inside form (for form submission)
- Both kept synchronized on add/remove operations

---

## Frontend Integration

### CSS Styling (assets/css/barter.css)

#### Single Listing Display
```css
/* Trade Description Section */
.trade-option-area {
    color: #282828;
    font-size: 23px;
    margin-top: 50px;
}

.trade-option-description {
    background: #fff;
    border-radius: 30px;
    padding: 30px;
}

/* Pink/Red Tags */
#tags-ul li a {
    background: #FFCED5;
    color: #F85C71;
    border-radius: 16px;
    padding: 10px 15px;
}

#tags-ul li a:hover {
    background: #f85c71;
    color: #fff;
}
```

#### Search Form Styling
```css
/* Input matches keyword field styling */
.classima-listing-search-form .rtin-barter input {
    border: none;
    padding: 0 0 0 10px;
    height: 58px;
    background: transparent;
}

/* Font Awesome icon before input */
.classima-listing-search-form .rtin-barter:before {
    content: "\f02b";  /* Tag icon */
    font-family: "Font Awesome 5 Free";
}
```

#### Selected Tags Row
```css
/* Grey background bar below search */
.wh-selected-tags-row {
    background: #f9f9f9;
    border-top: 1px solid #e1e1e1;
    padding: 10px 20px;
}

/* Small grey tags */
.wh-selected-tag {
    background: #f0f0f0;
    color: #666;
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 12px;
}
```

#### Autocomplete Dropdown
```css
.wh-tag-suggestions {
    position: absolute;
    background: white;
    border: 1px solid #ddd;
    z-index: 99999;
    max-height: 200px;
    overflow-y: auto;
}

.wh-tag-suggestion-item {
    padding: 10px 15px;
    cursor: pointer;
    background: white !important;
}

.wh-tag-suggestion-item:hover {
    background: #dadada !important;
}
```

---

## Technical Decisions

### Decision 1: JSON for Tag Storage
**Reasoning:**
- Tags are simple string array
- No complex querying needed (LIKE search sufficient)
- Avoids many-to-many relationship table
- Keeps database simple and performant

**Trade-offs:**
- ❌ Can't do efficient JOIN queries on tags
- ✅ Simple to implement and understand
- ✅ Fast for our use case
- ✅ Easy to maintain

### Decision 2: JavaScript Field Injection
**Reasoning:**
- Classima theme's banner search uses custom template with no action hooks
- Modifying theme files breaks on updates
- JavaScript injection maintains upgrade compatibility

**Implementation:**
- Detects all `.classima-listing-search-form` instances
- Creates unique IDs to avoid conflicts
- Uses event delegation for dynamic elements
- Works with both mobile and desktop forms

### Decision 3: Separated Visual and Form Data
**Reasoning:**
- Selected tags row is outside form (cleaner UI)
- Hidden inputs inside form (proper submission)
- Synchronized via JavaScript

**Benefits:**
- ✅ Clean, professional UI
- ✅ Tags don't clutter search inputs
- ✅ Clear "Selected tags:" label
- ✅ Still submits correctly with form

### Decision 4: Autocomplete-Only Tag Selection
**Reasoning:**
- Prevents typos and inconsistent tag names
- Ensures tags match existing database entries
- Improves search accuracy

**Implementation:**
- Enter key disabled from adding arbitrary text
- Tags can ONLY be added by clicking suggestions
- User must type at least 2 characters to see suggestions

### Decision 5: High Priority Query Filter
**Reasoning:**
- RTCL and theme run multiple `pre_get_posts` filters
- Lower priority filters can be overridden
- Priority 999 ensures barter filter runs last

**Result:**
- ✅ Barter tags correctly filter results
- ✅ Works with all RTCL search parameters
- ✅ Properly intersects with existing filters

---

## File Structure

### Main Plugin File (webhoma-subscription.php)

```php
<?php
/**
 * Plugin Name: Classima VIP Plugin
 * Version: 1.07
 * Description: Premium features for Classima theme
 */

// Constants
define('WH_SUB_VERSION', '1.0.7');
define('WH_SUB_DIR', plugin_dir_path(__FILE__));
define('WH_SUB_URL', plugin_dir_url(__FILE__));

// Activation hook
register_activation_hook(__FILE__, 'wh_sub_activate');

// Include files
require_once WH_SUB_DIR . 'install.php';
require_once WH_SUB_DIR . 'functions/barter.php';
require_once WH_SUB_DIR . 'functions/tokens.php';
require_once WH_SUB_DIR . 'functions/phone-reveal.php';
require_once WH_SUB_DIR . 'ajax/barter-ajax.php';
require_once WH_SUB_DIR . 'ajax/phone-ajax.php';

// Enqueue assets
add_action('wp_enqueue_scripts', 'wh_sub_enqueue_assets');

// Register hooks
add_action('rtcl_listing_form', 'wh_sub_add_fields', 25);
add_action('rtcl_listing_form_after_save_or_update', 'wh_sub_save_barter_data');
add_action('rtcl_single_listing_content_end', 'wh_sub_display_info', 15);
add_action('rtcl_widget_search_inline_form', 'wh_sub_search_filter', 20);
add_action('rtcl_widget_search_vertical_form', 'wh_sub_search_filter', 20);
add_action('pre_get_posts', 'wh_sub_filter_query', 999);
add_action('rtcl_after_listing_loop_thumbnail', 'wh_sub_add_badge');
```

### Installation (install.php)

Creates database table on plugin activation:
```php
function wh_sub_create_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE {$wpdb->prefix}barter_data (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        listing_id bigint(20) NOT NULL,
        description text,
        tags text,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY listing_id (listing_id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
```

---

## Configuration

### Plugin Settings
Currently hardcoded, can be made configurable:
- Autocomplete minimum characters: 2
- Maximum suggestions shown: 10
- Tag suggestion debounce: 300ms
- Query filter priority: 999

### WordPress Requirements
- WordPress 5.0+
- PHP 7.4+
- Classima Theme
- RTCL (Classified Listing) plugin
- RTCL Pro (optional but recommended)

### Browser Requirements
- Modern browsers (Chrome, Firefox, Safari, Edge)
- JavaScript enabled
- CSS3 support for styling

---

## Version History

### v1.07 (2026-02-19)
- ✅ Clean selected tags display below search form
- ✅ Fixed tag removal and addition with synchronized hidden inputs
- ✅ Auto show/hide tags row
- ✅ Separated visual display from form data

### v1.06 (2026-02-19)
- ✅ Fixed autocomplete dropdown not showing
- ✅ Moved tags outside input button wrapper
- ✅ Added proper positioning and overflow properties

### v1.05 (2026-02-19)
- ✅ Fixed duplicate ID issue (field injected in mobile + desktop)
- ✅ Changed to class-based selectors with unique IDs
- ✅ Context-aware suggestion selection

### v1.04 (2026-02-19)
- ✅ Fixed search query filter (wrong hook)
- ✅ Changed from rtcl_listing_query_args to pre_get_posts
- ✅ Improved query detection for archives and taxonomies

### v1.03 (2026-02-19)
- ✅ Made Trade Description tags clickable
- ✅ Tags redirect to listings with that barter tag
- ✅ Fixed search results showing irrelevant listings

### v1.02 (2026-02-18)
- ✅ Fixed backslash escaping in descriptions
- ✅ Added loading indicator for autocomplete
- ✅ Removed empty space below tag input

### v1.01 (2026-02-17)
- ✅ Updated barter display to match frontend template design
- ✅ Pink/red tags with rounded corners
- ✅ Responsive styling

### v1.00 (2026-02-15)
- ✅ Initial barter system implementation
- ✅ Database table creation
- ✅ Form fields integration
- ✅ Tag autocomplete
- ✅ Search filter
- ✅ Badge display

---

## Additional Notes

### Security
- All AJAX requests use WordPress nonces
- All database queries use prepared statements
- All outputs are escaped
- All inputs are sanitized

### Performance
- Indexes on database tables for fast queries
- AJAX requests debounced to reduce server load
- Autocomplete limited to 10 results
- Query filter optimized with post__in

### Compatibility
- Works with Classima theme versions 1.0+
- Compatible with RTCL 2.0+
- Multisite compatible (uses $wpdb->prefix)
- Translation ready

### Future Enhancements
- Admin settings page for configuration
- Tag management interface
- Tag statistics and analytics
- Bulk tag operations
- Tag synonyms/aliases

---

**End of Documentation**

**Plugin Version:** 1.07
**Documentation Updated:** 2026-02-19
**Status:** Production Ready
