# Token System - Implementation Guide

## Overview
Basic token system integrated with RTCL phone number reveal functionality. Users spend tokens to view phone numbers, and numbers are only charged once per listing.

---

## Database Tables Created

### 1. `wp_user_tokens`
Stores user token balances
- `user_id` - WordPress user ID (unique)
- `limited_tokens` - Tokens with expiry date
- `limited_expiry` - Expiration datetime
- `unlimited_tokens` - Tokens without expiry
- `auto_renew` - Auto-renewal flag (for future)
- `renewal_plan_id` - Linked plan ID (for future)

### 2. `wp_token_logs`
Transaction history for all token actions
- `user_id` - WordPress user ID
- `action_type` - add, deduct, expire, etc.
- `amount` - Number of tokens
- `listing_id` - Related listing (if applicable)
- `description` - Log message
- `created_at` - Timestamp

### 3. `wp_viewed_listings`
Tracks which listings each user has already viewed
- `user_id` - WordPress user ID
- `listing_id` - RTCL listing ID
- `viewed_at` - Timestamp
- **Unique constraint**: user_id + listing_id (prevents duplicates)

---

## Core Functions

### Token Management
Located in: `functions/tokens.php`

**Get Balance:**
```php
// Get user's token record
$token_data = wh_sub_get_user_tokens( $user_id );

// Get total available tokens (checks expiry)
$available = wh_sub_get_available_tokens( $user_id );
```

**Add Tokens:**
```php
// Add unlimited tokens
wh_sub_add_tokens( $user_id, 10, 'unlimited' );

// Add limited tokens with expiry
$expiry = date( 'Y-m-d H:i:s', strtotime( '+30 days' ) );
wh_sub_add_tokens( $user_id, 5, 'limited', $expiry );
```

**Deduct Tokens:**
```php
// Deduct 1 token for phone reveal
$success = wh_sub_deduct_tokens( $user_id, 1, $listing_id, 'Phone revealed' );
// Returns true if successful, false if insufficient tokens
```

### Viewing History
```php
// Check if user already viewed a listing
$viewed = wh_sub_has_viewed_listing( $user_id, $listing_id );

// Mark listing as viewed
wh_sub_mark_listing_viewed( $user_id, $listing_id );
```

### Transaction Logs
```php
// Get user's transaction history (last 20)
$logs = wh_sub_get_user_logs( $user_id, 20 );
```

---

## Phone Reveal System

### How It Works:

1. **First Visit**: User sees "Reveal Phone (1 Token)" button
2. **Click**: AJAX request checks tokens → deducts 1 token → shows phone
3. **Marks as Viewed**: Listing saved to `wp_viewed_listings`
4. **Next Visit**: Phone number shown directly (no charge)

### Integration:
Located in: `functions/phone-reveal.php`

Hooks into RTCL:
- Removes: `TemplateHooks::seller_phone_whatsapp_number` (priority 20)
- Adds: `wh_sub_custom_phone_display` (priority 20)

### AJAX Handler:
Located in: `ajax/phone-ajax.php`

Action: `wh_sub_reveal_phone`
- Verifies user is logged in
- Checks if already viewed
- Checks token balance
- Deducts 1 token
- Marks as viewed
- Returns phone number

---

## User Dashboard

### Shortcode:
```
[wh_token_dashboard]
```

### What It Shows:
1. **Token Balance Card**:
   - Total available tokens
   - Breakdown: unlimited vs limited
   - Expiry dates for limited tokens

2. **Token Usage History**:
   - Date/time of each transaction
   - Action type (add/deduct)
   - Amount (+/-)
   - Description

### Usage:
Add the shortcode to any page or post:
1. Create a page called "My Tokens"
2. Add shortcode: `[wh_token_dashboard]`
3. Publish

---

## Testing & Demo

### Add Test Tokens:
For admins only - add tokens via URL parameter:

```
http://classima.local/wp-admin/?wh_add_tokens=10
```

This will add 10 unlimited tokens to your account.

### Test Flow:
1. Add tokens to your account (see above)
2. Visit any listing with a phone number
3. Click "Reveal Phone (1 Token)" button
4. Phone number appears
5. Refresh page - phone shows directly (no button)
6. Check dashboard to see transaction log

---

## File Structure

```
webhoma-subscription/
├── functions/
│   ├── barter.php         # Barter system (existing)
│   ├── tokens.php         # Token management functions
│   └── phone-reveal.php   # Phone reveal UI
├── ajax/
│   ├── barter-ajax.php    # Barter AJAX (existing)
│   └── phone-ajax.php     # Phone reveal AJAX
├── assets/
│   ├── css/
│   │   ├── barter.css             # Barter styles (existing)
│   │   └── phone-reveal.css       # Token system styles
│   └── js/
│       ├── barter.js              # Barter JS (existing)
│       └── phone-reveal.js        # Phone reveal JS
├── install.php            # Database table creation
├── admin-helper.php       # Testing utilities
└── webhoma-subscription.php  # Main plugin file
```

---

## Token Priority System

When deducting tokens:
1. **Limited tokens** consumed first (if not expired)
2. **Unlimited tokens** consumed second
3. Returns `false` if insufficient tokens

Example:
- User has: 3 limited tokens (expires in 5 days) + 10 unlimited
- After 3 phone reveals: 0 limited, 10 unlimited
- After 13 phone reveals: 0 limited, 0 unlimited (blocked)

---

## Next Steps (Future Development)

1. **WooCommerce Integration**:
   - Create subscription products
   - Link products to token packages
   - Handle purchases and renewals

2. **Admin Panel**:
   - Manage user tokens
   - View all transactions
   - Create/edit subscription plans

3. **Cron Jobs**:
   - Auto-expire limited tokens
   - Auto-renewal processing
   - Email notifications

4. **Additional Features**:
   - Token packages with pricing
   - Subscription plans (monthly/yearly)
   - Purchase history
   - Payment gateway integration

---

## Important Notes

- Only logged-in users can reveal phone numbers
- Guest users see nothing (system hidden)
- Listing authors always see their own phone numbers
- Tables created automatically on plugin activation
- All token actions are logged for audit trail
- Phone numbers stored in post meta: `phone`

---

## Support

For issues or questions, contact: https://webhoma.ir
