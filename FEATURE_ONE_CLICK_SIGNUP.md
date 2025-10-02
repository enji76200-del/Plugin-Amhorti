# One-Click Signup/Unsubscribe Feature

## Overview
This feature adds small + (green) and − (red) action icons to each editable booking cell, allowing users to instantly sign up or unsubscribe with a single click.

## Key Features

### ✅ Quick Signup (+ Icon)
- Click the green + icon in any empty cell
- Automatically fills cell with "login N." format
- N = uppercase first letter of last name
- Supports Unicode (Cyrillic, Greek, Arabic, etc.)
- Tracks ownership via user_id

### ✅ Quick Delete (− Icon)
- Click the red − icon on your own bookings
- Instantly removes the booking
- Only works if you're the owner OR an admin
- Respects ownership and permissions

### ✅ Visual Feedback
- Icons appear on hover (smooth fade-in)
- Yellow background during save ("Saving...")
- Green flash on success
- Error messages displayed if operation fails
- Button disabled to prevent double-clicks

### ✅ Security
- Nonce verification on all AJAX requests
- User authentication required
- Ownership validation on deletion
- Admin override capability
- Date range validation

### ✅ Backward Compatible
- Free-text editing still works by typing
- +/− icons are shortcuts, not replacements
- No breaking changes to existing functionality

## Usage

### For Regular Users
1. Hover over an empty cell → green + icon appears
2. Click + to sign up instantly with your auto-generated label
3. Hover over your booking → red − icon appears
4. Click − to delete your booking

### For Administrators
- Same as regular users, plus:
- Can see − icon on ALL bookings (not just own)
- Can delete any booking

## Technical Details

### Files Modified
- `assets/css/public.css` - Action icon styles
- `assets/js/public.js` - Event handlers and AJAX
- `includes/class-amhorti-public.php` - Label generation and AJAX handlers
- `includes/class-amhorti-database.php` - Helper method

### New AJAX Endpoints
- `amhorti_quick_signup` - Quick signup handler
- `amhorti_quick_delete` - Quick delete handler

### Icon Visibility Rules

**+ Icon shows when:**
- User is logged in
- Date is within valid range
- Cell is empty

**− Icon shows when:**
- User is logged in
- Date is within valid range
- Cell has a booking
- User is owner OR admin

## Examples

### Label Generation
```
john + Doe → "john D."
ivan + Иванов → "ivan И." (Cyrillic)
alex + Αλεξόπουλος → "alex Α." (Greek)
maria + García → "maria G." (Latin with accent)
```

## Testing
✅ All 12 tests passed:
- 6/6 Unicode support tests
- 6/6 Icon visibility logic tests
- PHP syntax validation passed

## Status
✅ Ready for production
