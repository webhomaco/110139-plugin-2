# Classima VIP Plugin

Premium subscription and barter system for classified listing websites using RTCL plugin.

## What This Plugin Does

**Token System:**
- Users purchase tokens to view phone numbers on listings
- Each phone number costs 1 token (charged only once per listing)
- Token balance tracked with limited and unlimited token types
- Transaction history and logs for all token activities

**Barter/Trade System:**
- Sellers can indicate willingness to trade/barter their items
- Tag-based system to specify what items they want in exchange
- Buyers can search and filter listings by barter tags
- Trade badges displayed on listing cards

## Requirements

- WordPress 5.0+
- PHP 7.4+
- Classified Listing (RTCL) plugin
- Classima theme

## Current Status

**Completed:**
- Token management system
- Phone number reveal with token payment
- Barter/trade functionality with tags
- User dashboard showing token balance and usage
- Database tables and core functions

**Pending:**
- WooCommerce payment integration
- Admin panel for subscription management
- Auto-renewal and expiration system

## Installation

1. Upload plugin folder to `/wp-content/plugins/`
2. Activate through WordPress admin
3. Database tables created automatically

## User Dashboard

Add shortcode to any page to show token dashboard:
```
[wh_token_dashboard]
```

## License

GPL v2 or later
