# ChiBank Payment System

A comprehensive payment and banking system built with Laravel.

## Features

- Multi-currency support
- Payment gateways integration
- Virtual card management
- QR code payments
- **OAuth Social Login** - Google, Facebook, Alipay, WeChat, and Crypto Wallets
- Mobile money transfers
- Bill payments
- And more...

## New: OAuth Social Login 🎉

This application now supports multiple OAuth providers for user authentication:

- **Google** - Login with Google account
- **Facebook** - Login with Facebook account
- **Alipay** - Login with Alipay account (Chinese users)
- **WeChat** - Login with WeChat account (Chinese users)
- **Crypto Wallets** - Login with MetaMask, WalletConnect, or Trust Wallet

### Quick Setup

1. Run the database migration:
   ```bash
   php artisan migrate
   ```

2. Configure OAuth providers in the admin panel:
   - Go to **Admin Dashboard > OAuth Settings**
   - Enter your provider credentials
   - Save and test

3. Users can now login using social accounts on the login page!

For detailed setup instructions, see [OAUTH_SETUP_GUIDE.md](OAUTH_SETUP_GUIDE.md)

## Installation

1. Clone the repository
2. Run `composer install`
3. Copy `.env.example` to `.env` and configure your database
4. Run `php artisan key:generate`
5. Run `php artisan migrate`
6. Configure your OAuth providers (optional)
7. Start the development server: `php artisan serve`

## Documentation

- [OAuth Setup Guide](OAUTH_SETUP_GUIDE.md) - Complete guide for configuring social login
- [Implementation Summary](IMPLEMENTATION_SUMMARY.md) - Technical details of OAuth implementation

## Requirements

- PHP >= 8.0.2
- MySQL or PostgreSQL
- Composer
- Node.js & NPM (for frontend assets)

## License

MIT License

## Support

For issues or questions, please open a GitHub issue.
