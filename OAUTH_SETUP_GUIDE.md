# OAuth Social Login Setup Guide

This application now supports multiple OAuth providers for user authentication including Google, Facebook, Alipay, WeChat, and cryptocurrency wallet logins.

## Overview

Users can login using:
- **Google** - OAuth 2.0
- **Facebook** - OAuth 2.0
- **Alipay** - Alipay Open Platform
- **WeChat** - WeChat Open Platform
- **Crypto Wallets** - MetaMask, WalletConnect, Trust Wallet

## Database Changes

A new migration has been added: `2025_11_18_063800_add_oauth_fields_to_users_table.php`

This adds two fields to the `users` table:
- `oauth_provider` - The provider name (google, facebook, alipay, wechat, wallet)
- `oauth_provider_id` - The unique ID from the provider

Run the migration:
```bash
php artisan migrate
```

## Configuration

### 1. Environment Variables

Add the following to your `.env` file (already added to `.env.example`):

```env
# Google OAuth
GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret
GOOGLE_CALLBACK=https://yourdomain.com/auth/google/callback

# Facebook OAuth
FACEBOOK_CLIENT_ID=your_facebook_app_id
FACEBOOK_CLIENT_SECRET=your_facebook_app_secret
FACEBOOK_CALLBACK=https://yourdomain.com/auth/facebook/callback

# Alipay OAuth
ALIPAY_APP_ID=your_alipay_app_id
ALIPAY_PRIVATE_KEY=your_alipay_private_key
ALIPAY_PUBLIC_KEY=your_alipay_public_key
ALIPAY_CALLBACK=https://yourdomain.com/auth/alipay/callback

# WeChat OAuth
WECHAT_APP_ID=your_wechat_app_id
WECHAT_APP_SECRET=your_wechat_app_secret
WECHAT_CALLBACK=https://yourdomain.com/auth/wechat/callback

# Wallet Providers
WALLET_METAMASK_ENABLED=true
WALLET_WALLETCONNECT_ENABLED=true
WALLETCONNECT_PROJECT_ID=your_walletconnect_project_id
WALLET_TRUST_ENABLED=true
```

### 2. Admin Configuration

Access the OAuth settings page in the admin panel:
**Admin Dashboard > OAuth Settings**

Here you can configure all OAuth providers without directly editing the `.env` file.

## Provider Setup Instructions

### Google OAuth Setup

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select an existing one
3. Enable Google+ API
4. Go to Credentials > Create Credentials > OAuth 2.0 Client ID
5. Configure OAuth consent screen
6. Add authorized redirect URI: `https://yourdomain.com/auth/google/callback`
7. Copy Client ID and Client Secret to your configuration

### Facebook OAuth Setup

1. Go to [Facebook Developers](https://developers.facebook.com/)
2. Create a new app or select an existing one
3. Add Facebook Login product
4. Configure OAuth Redirect URIs: `https://yourdomain.com/auth/facebook/callback`
5. Copy App ID and App Secret to your configuration

### Alipay Setup

1. Register at [Alipay Open Platform](https://open.alipay.com/)
2. Create a new application
3. Generate RSA key pairs
4. Configure callback URL: `https://yourdomain.com/auth/alipay/callback`
5. Copy App ID, Private Key, and Public Key to your configuration

**Note:** For Alipay, you may need to install additional Socialite provider:
```bash
composer require socialiteproviders/alipay
```

Then add to `config/app.php`:
```php
'providers' => [
    // Other providers...
    \SocialiteProviders\Manager\ServiceProvider::class,
],
```

And add event listener in `app/Providers/EventServiceProvider.php`:
```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        'SocialiteProviders\\Alipay\\AlipayExtendSocialite@handle',
    ],
];
```

### WeChat Setup

1. Register at [WeChat Open Platform](https://open.weixin.qq.com/)
2. Create a new application (Website application)
3. Configure callback URL: `https://yourdomain.com/auth/wechat/callback`
4. Get App ID and App Secret from the platform
5. Copy to your configuration

**Note:** For WeChat, you may need to install additional Socialite provider:
```bash
composer require socialiteproviders/weixin-web
```

Then add to `config/app.php` and `EventServiceProvider.php` similar to Alipay.

### Wallet Login Setup

For cryptocurrency wallet authentication:

1. **MetaMask**: No additional setup required. Just enable in admin panel.
2. **WalletConnect**: 
   - Register at [WalletConnect Cloud](https://cloud.walletconnect.com/)
   - Create a project
   - Copy Project ID to configuration
3. **Trust Wallet**: No additional setup required. Just enable in admin panel.

## Routes

The following routes are available:

- `GET /auth/{provider}` - Redirect to OAuth provider
- `GET /auth/{provider}/callback` - OAuth callback handler
- `POST /auth/wallet` - Wallet authentication endpoint

## Frontend Integration

Social login buttons are automatically displayed on the user login page when providers are configured.

The buttons only appear if the provider credentials are set in the configuration.

## Security Considerations

1. Always use HTTPS in production
2. Keep OAuth secrets secure and never commit them to version control
3. Regularly rotate OAuth credentials
4. For wallet authentication, implement proper signature verification
5. Consider implementing rate limiting on OAuth endpoints

## User Flow

1. User clicks on a social login button
2. User is redirected to the OAuth provider
3. User authorizes the application
4. Provider redirects back to callback URL
5. Application verifies the authentication
6. If user exists (matched by email), link OAuth to existing account
7. If new user, create account automatically with OAuth data
8. User is logged in and redirected to dashboard

## Troubleshooting

### "Invalid OAuth provider" error
- Ensure the provider is one of: google, facebook, alipay, wechat
- Check that provider credentials are configured

### "OAuth provider not configured" error
- Verify that Client ID and Client Secret are set in environment or admin panel
- Clear config cache: `php artisan config:clear`

### Wallet login not working
- Ensure user has MetaMask or compatible wallet installed
- Check browser console for JavaScript errors
- Verify wallet provider is enabled in configuration

## Testing

To test OAuth integration:

1. Set up test credentials from each provider
2. Use provider's test/sandbox environment where available
3. Test the complete authentication flow
4. Verify user creation and login
5. Test linking OAuth to existing accounts

## Additional Packages (Optional)

For full support of Alipay and WeChat, install additional providers:

```bash
composer require socialiteproviders/alipay
composer require socialiteproviders/weixin-web
```

Then configure them as described in the provider-specific sections above.
