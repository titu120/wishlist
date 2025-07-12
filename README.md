# Advanced WooCommerce Wishlist

A comprehensive, production-ready WordPress plugin that adds advanced wishlist functionality to WooCommerce stores with multiple wishlists, price drop notifications, social sharing, and extensive customization options.

## Features

### Core Functionality
- ✅ **Multiple Wishlists**: Users can create, manage, and switch between multiple wishlists
- ✅ **Guest Wishlists**: Non-logged-in users can create wishlists using session storage
- ✅ **AJAX Operations**: All wishlist actions are handled via AJAX for smooth user experience
- ✅ **Price Drop Notifications**: Automatic tracking and notifications when wishlist items drop in price
- ✅ **Social Sharing**: Share wishlists via Facebook, Twitter, WhatsApp, and Email
- ✅ **Analytics Dashboard**: Comprehensive analytics and reporting for administrators

### User Experience
- ✅ **Responsive Design**: Mobile-friendly interface that works on all devices
- ✅ **Accessibility**: WCAG compliant with keyboard navigation and screen reader support
- ✅ **RTL Support**: Full right-to-left language support
- ✅ **Customizable Buttons**: Multiple button styles, colors, and sizes
- ✅ **Bulk Operations**: Add all wishlist items to cart at once
- ✅ **Export Functionality**: Export wishlist data in CSV or JSON format

### Admin Features
- ✅ **Settings Panel**: Comprehensive configuration options
- ✅ **Analytics Dashboard**: Detailed wishlist statistics and insights
- ✅ **Product Integration**: Wishlist buttons on product pages and loops
- ✅ **Bulk Actions**: Manage wishlist data from admin panel
- ✅ **Dashboard Widget**: Quick overview of wishlist activity

### Developer Friendly
- ✅ **Hooks & Filters**: Extensive WordPress hooks for customization
- ✅ **Shortcodes**: Multiple shortcodes for displaying wishlist content
- ✅ **API Methods**: Clean API for developers to extend functionality
- ✅ **Translation Ready**: Full internationalization support
- ✅ **Performance Optimized**: Efficient database queries and caching

## Installation

### Requirements
- WordPress 5.0 or higher
- WooCommerce 4.0 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher

### Installation Steps

1. **Upload Plugin**
   ```bash
   # Upload the plugin folder to wp-content/plugins/
   cp -r advanced-wc-wishlist /path/to/wp-content/plugins/
   ```

2. **Activate Plugin**
   - Go to WordPress Admin → Plugins
   - Find "Advanced WooCommerce Wishlist"
   - Click "Activate"

3. **Configure Settings**
   - Go to WooCommerce → Wishlist → Settings
   - Configure your preferred options
   - Save settings

4. **Add Wishlist Page** (Optional)
   - Create a new page
   - Add shortcode: `[aww_wishlist]`
   - Set as wishlist page in settings

## Configuration

### General Settings

| Setting | Description | Default |
|---------|-------------|---------|
| Enable Guest Wishlist | Allow non-logged-in users to create wishlists | Yes |
| Enable Social Sharing | Enable social media sharing buttons | Yes |
| Enable Multiple Wishlists | Allow users to create multiple wishlists | Yes |
| Max Wishlists per User | Maximum number of wishlists per user | 10 |
| Enable Price Drop Notifications | Track and notify about price drops | Yes |
| Price Drop Threshold | Minimum percentage drop to trigger notification | 5% |

### Button Settings

| Setting | Description | Default |
|---------|-------------|---------|
| Button Text | Text for wishlist button | "Add to Wishlist" |
| Button Text (Added) | Text when item is in wishlist | "Added to Wishlist" |
| Button Color | Primary button color | #e74c3c |
| Button Color (Hover) | Button color on hover | #c0392b |
| Button Style | Button appearance style | Default |
| Button Size | Button size | Medium |

### Display Settings

| Setting | Description | Default |
|---------|-------------|---------|
| Show Price | Display product prices in wishlist | Yes |
| Show Stock Status | Display stock availability | Yes |
| Show Date Added | Display when item was added | No |
| Show Product Image | Display product images | Yes |

## Usage

### Shortcodes

#### Basic Wishlist Display
```php
[aww_wishlist]
```

#### Wishlist with Custom Options
```php
[aww_wishlist wishlist_id="123" limit="20" show_price="yes" show_stock="yes"]
```

#### Wishlist Count
```php
[aww_wishlist_count wishlist_id="123" show_text="yes" show_icon="yes"]
```

#### Wishlist Button
```php
[aww_wishlist_button product_id="456" wishlist_id="123" style="default" size="medium"]
```

#### Wishlist Products Grid
```php
[aww_wishlist_products wishlist_id="123" limit="12" columns="4" show_price="yes"]
```

#### Popular Wishlisted Products
```php
[aww_popular_wishlisted limit="10" columns="4" show_count="yes"]
```

#### Multiple Wishlist Management
```php
[aww_wishlist_manager show_create="yes" show_rename="yes" show_delete="yes" show_selector="yes"]
```

#### Wishlist Selector
```php
[aww_wishlist_selector show_count="yes" style="dropdown"]
```

#### Price Drops Display
```php
[aww_price_drops wishlist_id="123" limit="10" show_discount="yes"]
```

### PHP API

#### Add Product to Wishlist
```php
// Add to default wishlist
AWW()->database->add_to_wishlist($product_id);

// Add to specific wishlist
AWW()->database->add_to_wishlist($product_id, $wishlist_id);
```

#### Remove Product from Wishlist
```php
// Remove from default wishlist
AWW()->database->remove_from_wishlist($product_id);

// Remove from specific wishlist
AWW()->database->remove_from_wishlist($product_id, $wishlist_id);
```

#### Check if Product is in Wishlist
```php
// Check in default wishlist
$is_in_wishlist = AWW()->database->is_product_in_wishlist($product_id);

// Check in specific wishlist
$is_in_wishlist = AWW()->database->is_product_in_wishlist($product_id, $wishlist_id);
```

#### Get Wishlist Items
```php
// Get items from default wishlist
$items = AWW()->database->get_wishlist_items();

// Get items from specific wishlist
$items = AWW()->database->get_wishlist_items($wishlist_id, $limit, $offset);
```

#### Create New Wishlist
```php
$wishlist_id = AWW()->database->create_wishlist($name, $user_id, $session_id);
```

#### Get User Wishlists
```php
$wishlists = AWW()->database->get_wishlists($user_id, $session_id);
```

#### Get Wishlist Count
```php
$count = AWW()->database->get_wishlist_count($wishlist_id);
```

### JavaScript API

#### Add to Wishlist
```javascript
// Add product to wishlist
$.ajax({
    url: aww_ajax.ajax_url,
    type: 'POST',
    data: {
        action: 'aww_add_to_wishlist',
        product_id: productId,
        wishlist_id: wishlistId,
        nonce: aww_ajax.nonce
    },
    success: function(response) {
        if (response.success) {
            console.log('Product added to wishlist');
        }
    }
});
```

#### Remove from Wishlist
```javascript
// Remove product from wishlist
$.ajax({
    url: aww_ajax.ajax_url,
    type: 'POST',
    data: {
        action: 'aww_remove_from_wishlist',
        product_id: productId,
        wishlist_id: wishlistId,
        nonce: aww_ajax.nonce
    },
    success: function(response) {
        if (response.success) {
            console.log('Product removed from wishlist');
        }
    }
});
```

#### Create Wishlist
```javascript
// Create new wishlist
$.ajax({
    url: aww_ajax.ajax_url,
    type: 'POST',
    data: {
        action: 'aww_create_wishlist',
        name: wishlistName,
        nonce: aww_ajax.nonce
    },
    success: function(response) {
        if (response.success) {
            console.log('Wishlist created:', response.data.wishlist_id);
        }
    }
});
```

## Hooks and Filters

### Actions

#### Product Added to Wishlist
```php
do_action('aww_product_added_to_wishlist', $product_id, $wishlist_id);
```

#### Product Removed from Wishlist
```php
do_action('aww_product_removed_from_wishlist', $product_id, $wishlist_id);
```

#### Wishlist Created
```php
do_action('aww_wishlist_created', $wishlist_id, $user_id);
```

#### Wishlist Deleted
```php
do_action('aww_wishlist_deleted', $wishlist_id, $user_id);
```

#### Price Drop Detected
```php
do_action('aww_price_drop_detected', $product_id, $old_price, $new_price, $wishlist_id);
```

### Filters

#### Wishlist Button HTML
```php
apply_filters('aww_wishlist_button_html', $html, $product_id, $wishlist_id);
```

#### Wishlist Count HTML
```php
apply_filters('aww_wishlist_count_html', $html, $count, $wishlist_id);
```

#### Wishlist URL
```php
apply_filters('aww_wishlist_url', $url, $wishlist_id);
```

#### Price Drop Threshold
```php
apply_filters('aww_price_drop_threshold', $threshold, $product_id);
```

## Database Schema

### Wishlist Lists Table
```sql
CREATE TABLE wp_aww_wishlist_lists (
    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    user_id bigint(20) unsigned DEFAULT NULL,
    session_id varchar(255) DEFAULT NULL,
    name varchar(255) NOT NULL,
    date_created datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_updated datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY user_id (user_id),
    KEY session_id (session_id)
);
```

### Wishlist Items Table
```sql
CREATE TABLE wp_aww_wishlists (
    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    wishlist_id bigint(20) unsigned NOT NULL,
    product_id bigint(20) unsigned NOT NULL,
    price_at_add decimal(20,6) DEFAULT NULL,
    date_added datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY wishlist_id (wishlist_id),
    KEY product_id (product_id),
    KEY date_added (date_added),
    UNIQUE KEY unique_wishlist_item (wishlist_id, product_id)
);
```

## Customization

### Custom Button Styles

Add custom CSS to your theme:
```css
.aww-wishlist-btn.custom-style {
    background: linear-gradient(45deg, #ff6b6b, #ee5a24);
    border-radius: 25px;
    box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3);
}

.aww-wishlist-btn.custom-style:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(255, 107, 107, 0.4);
}
```

### Custom Templates

Override templates by copying them to your theme:
```
your-theme/
├── advanced-wc-wishlist/
│   ├── wishlist-button.php
│   ├── wishlist-page.php
│   └── wishlist-table.php
```

### Custom JavaScript

Extend functionality with custom JavaScript:
```javascript
// Listen for wishlist events
$(document).on('aww_product_added', function(event, productId, data) {
    console.log('Product added:', productId);
    // Your custom code here
});

$(document).on('aww_product_removed', function(event, productId, data) {
    console.log('Product removed:', productId);
    // Your custom code here
});
```

## Troubleshooting

### Common Issues

#### Wishlist Button Not Showing
1. Check if WooCommerce is active
2. Verify product is published and visible
3. Check theme compatibility
4. Review JavaScript console for errors

#### AJAX Not Working
1. Ensure jQuery is loaded
2. Check nonce verification
3. Verify AJAX URL is correct
4. Check server error logs

#### Price Drop Notifications Not Working
1. Verify cron jobs are running
2. Check notification settings
3. Ensure products have prices
4. Review email server configuration

#### Database Issues
1. Run database upgrade: `wp aww upgrade`
2. Check table permissions
3. Verify MySQL version compatibility
4. Review error logs

### Debug Mode

Enable debug mode in wp-config.php:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

Check debug log for detailed error information.

## Performance Optimization

### Caching
- Enable object caching (Redis/Memcached)
- Use CDN for static assets
- Implement page caching

### Database Optimization
- Regular database cleanup
- Optimize wishlist queries
- Use database indexes

### Asset Optimization
- Minify CSS and JavaScript
- Optimize images
- Use lazy loading

## Security

### Data Protection
- All user data is encrypted
- Secure session handling
- Input sanitization and validation
- SQL injection prevention

### Access Control
- Role-based permissions
- Nonce verification
- Capability checks
- Rate limiting

## Support

### Documentation
- [Plugin Documentation](https://example.com/docs)
- [API Reference](https://example.com/api)
- [FAQ](https://example.com/faq)

### Support Channels
- [GitHub Issues](https://github.com/example/advanced-wc-wishlist/issues)
- [Support Forum](https://example.com/support)
- [Email Support](mailto:support@example.com)

### Contributing
1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Submit a pull request

## Changelog

### Version 1.0.0
- Initial release
- Multiple wishlist support
- Price drop notifications
- Social sharing
- Analytics dashboard
- Guest wishlist functionality
- AJAX operations
- Responsive design
- Accessibility features
- Translation support

## License

This plugin is licensed under the GPL v2 or later.

## Credits

- Built with WordPress and WooCommerce
- Icons by [Font Awesome](https://fontawesome.com/) (with CDN fallback support)
- Charts by [Chart.js](https://www.chartjs.org/)

## Font Awesome Integration

This plugin includes intelligent Font Awesome integration with automatic fallback to WordPress Dashicons:

### Features
- **Automatic Detection**: Detects if Font Awesome is already loaded by the theme
- **CDN Fallback**: Loads Font Awesome 6 Free from CDN if not present
- **Admin Control**: Option to enable/disable Font Awesome CDN loading
- **Graceful Degradation**: Falls back to WordPress Dashicons if Font Awesome fails to load
- **WordPress.org Compliant**: Uses approved CDN (cdnjs.cloudflare.com)

### Configuration
1. Go to **WooCommerce > Wishlist > Sharing Options**
2. Enable/disable "Enable Font Awesome CDN" option
3. The plugin will automatically handle icon display

### How It Works
1. Plugin checks if Font Awesome is already loaded by the theme
2. If not loaded and CDN is enabled, loads Font Awesome 6 Free from CDN
3. Social sharing buttons display Font Awesome icons with Dashicons fallback
4. JavaScript detects Font Awesome availability and switches icons accordingly

### CDN Details
- **URL**: `https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css`
- **Version**: Font Awesome 6.4.0 Free
- **Provider**: Cloudflare CDN (WordPress.org approved)
- **License**: Font Awesome Free License

---

**Advanced WooCommerce Wishlist** - The most comprehensive wishlist solution for WooCommerce stores. # wishlist
