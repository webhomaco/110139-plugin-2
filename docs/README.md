# Webhoma Barter System Plugin

WordPress plugin that adds barter/trade functionality to the Classified Listing (RTCL) plugin on Classima theme.

## Features

✅ Add barter/trade option when submitting listings
✅ Tag-based system with autocomplete
✅ Display barter information on listing pages
✅ Search/filter listings by barter tags
✅ "Trade Available" badge on listing cards
✅ Clean, simple code - no ORM overhead

## Requirements

- WordPress 5.0+
- PHP 7.4+
- Classified Listing (RTCL) plugin
- Classima theme

## Installation

1. Upload the `webhoma-barter` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Database table will be created automatically on activation

## Usage

### For Users (Listing Submission)

1. Go to Submit Listing page
2. Scroll to "Trade Option (Barter)" section
3. Enter trade description (what you want to trade for)
4. Add tags by typing and pressing Enter
5. Tags will autocomplete from existing tags
6. Submit listing

### For Users (Searching)

1. Go to listings archive/search page
2. Find "Trade Tags" filter
3. Type tags and press Enter
4. Multiple tags can be selected
5. Submit search

### Displaying Barter Info

Barter information automatically displays on:
- Single listing pages (after listing details)
- Listing cards (badge in corner)

## File Structure

```
webhoma-barter/
├── webhoma-barter.php          # Main plugin file
├── install.php                 # Database installation
├── functions/
│   └── barter.php             # Core functions
├── ajax/
│   └── barter-ajax.php        # AJAX handlers
├── assets/
│   ├── css/
│   │   └── barter.css         # Styles
│   └── js/
│       └── barter.js          # JavaScript
└── README.md                   # This file
```

## Database Schema

Table: `wp_barter_data`

```sql
id              bigint(20)    PRIMARY KEY AUTO_INCREMENT
listing_id      bigint(20)    Listing post ID
description     text          Trade description
tags            text          JSON array of tags
created_at      datetime      Creation timestamp
```

## Hooks Used

### Actions
- `rtcl_listing_form` - Add form fields
- `rtcl_listing_form_after_save_or_update` - Save data
- `rtcl_single_listing_content_end` - Display info
- `rtcl_widget_search_form` - Add search filter
- `rtcl_after_listing_loop_thumbnail` - Add badge

### Filters
- `rtcl_listing_query_args` - Filter listings by tags

## Customization

### Change Badge Color
Edit `assets/css/barter.css`:
```css
.wh-barter-badge {
    background: #your-color;
}
```

### Change Tag Appearance
Edit `assets/css/barter.css`:
```css
.wh-tag-badge {
    background: #your-color;
}
```

## Development

Built with WordPress best practices:
- Uses `$wpdb` directly (no ORM)
- Follows WordPress coding standards
- Proper sanitization and escaping
- Nonce verification for AJAX
- Internationalization ready

## Support

For issues or questions, contact: https://webhoma.ir

## License

GPL v2 or later

## Changelog

### 1.0.0
- Initial release
- Basic barter functionality
- Tag autocomplete
- Search filtering
- Badge display
