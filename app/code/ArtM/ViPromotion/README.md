# ArtM ViPromotion Module

## Overview
The ViPromotion module provides customer group-specific promotional features including custom logos, login popups, and account page banners specifically for VIP customers.

## Features

### 1. Custom Logo for VIP Customers
- Replaces the default store logo with a custom VIP logo
- Only visible to customers in the configured customer group
- Adds a "VIP" badge indicator
- Fallback to default logo for non-VIP customers

### 2. Login Popup (Once per Day)
- Shows a promotional popup after customer login
- Displays only once per day per customer
- Fully customizable title and content
- Responsive design with smooth animations

### 3. Account Page Banner
- Displays a promotional banner on the customer account dashboard
- Only visible to VIP customer group members
- Fully customizable HTML content

## Configuration

### Admin Configuration
Navigate to: **Stores → Configuration → ArtM → VIP Promotion**

#### General Settings
- **Enable VIP Promotion**: Enable/disable the entire module
- **Target Customer Group**: Select which customer group should see VIP features (default: Group ID 5)

#### Custom Logo Settings
- **Custom Logo Image**: Upload a custom logo image for VIP customers
- Supported formats: JPG, PNG, GIF
- Recommended size: Maximum height 50px for best display

#### Login Popup Settings
- **Enable Login Popup**: Enable/disable the login popup feature
- **Popup Title**: Customize the popup title
- **Popup Content**: Customize the popup content (HTML supported)

#### Account Page Banner Settings
- **Enable Account Banner**: Enable/disable the account page banner
- **Banner Content**: Customize the banner content (HTML supported)

## Installation

1. Copy the module files to: `app/code/ArtM/ViPromotion/`
2. Run the following Magento CLI commands:
   ```bash
   php bin/magento module:enable ArtM_ViPromotion
   php bin/magento setup:upgrade
   php bin/magento setup:di:compile
   php bin/magento setup:static-content:deploy
   php bin/magento cache:flush
   ```

## File Structure
```
app/code/ArtM/ViPromotion/
├── Block/
│   ├── Banner.php          # Account page banner block
│   ├── Logo.php            # Custom logo block
│   └── Popup.php           # Login popup block
├── Helper/
│   └── Data.php            # Main helper class
├── Observer/
│   └── CustomerLogin.php   # Login event observer
├── etc/
│   ├── acl.xml            # Admin ACL permissions
│   ├── config.xml         # Default configuration
│   ├── di.xml             # Dependency injection
│   ├── events.xml         # Event observers
│   ├── module.xml         # Module declaration
│   └── adminhtml/
│       └── system.xml     # Admin system configuration
├── view/frontend/
│   ├── layout/
│   │   ├── default.xml                    # Default layout (logo + popup)
│   │   └── customer_account_index.xml     # Account page layout (banner)
│   └── templates/
│       ├── banner.phtml   # Account banner template
│       ├── logo.phtml     # Custom logo template
│       └── popup.phtml    # Login popup template
└── registration.php       # Module registration
```

## Customization

### Customer Group Configuration
The module uses customer group ID 5 as default. To change this:
1. Go to Admin → Stores → Configuration → ArtM → VIP Promotion → General Settings
2. Select your desired customer group from the dropdown

### Styling Customization
All CSS styles are embedded in the template files for easy customization:
- Logo styles: `view/frontend/templates/logo.phtml`
- Popup styles: `view/frontend/templates/popup.phtml`
- Banner styles: `view/frontend/templates/banner.phtml`

### Content Customization
- Popup and banner content support full HTML
- Use the admin configuration panel for easy content management
- Default content is provided but can be completely customized

## Technical Details

### Customer Group Detection
The module automatically detects the customer's group and only displays VIP features to members of the configured target group.

### Popup Frequency Control
The popup display frequency is controlled using Magento's session storage, ensuring it shows only once per day per customer.

### Logo Fallback
If no custom logo is uploaded or the customer is not in the target group, the module gracefully falls back to the default store logo.

## Troubleshooting

### Logo Not Showing
1. Verify the customer is logged in and belongs to the target customer group
2. Check if a custom logo image has been uploaded in the admin configuration
3. Clear Magento cache: `php bin/magento cache:flush`

### Popup Not Appearing
1. Ensure the popup is enabled in admin configuration
2. Verify the customer belongs to the target customer group
3. Check if the popup was already shown today (it only shows once per day)

### Banner Not Displaying
1. Confirm the banner is enabled in admin configuration
2. Verify you're on the customer account dashboard page
3. Check if the customer belongs to the target customer group

## Support
For support or customization requests, please contact the ArtM development team.

## Version
Current Version: 1.0.0

## Compatibility
- Magento 2.3.x and higher
- PHP 7.1 and higher