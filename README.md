# ChiBank/QRPay v5.0.0

Enterprise-grade digital payment gateway solution built on Laravel.

## 📚 Documentation

### Chinese (中文)
- [操作文档 (Operation Manual)](docs/zh-CN/操作文档.md)
- [白皮书 (White Paper)](docs/zh-CN/白皮书.md)

### English
- [Operation Manual](docs/en/OPERATION-MANUAL.md)
- [White Paper](docs/en/WHITEPAPER.md)

## 🚀 Quick Start

```bash
# Clone the repository
git clone https://github.com/LILIANSRL/chibank-.git
cd chibank-

# Install dependencies
composer install
npm install

# Configure environment
cp .env.example .env
php artisan key:generate

# Run migrations
php artisan migrate
php artisan db:seed

# Start the application
php artisan serve
```

## 🔧 Features

- Multi-role support (Users, Agents, Merchants, Admin)
- Multiple payment gateways (Stripe, PayPal, Flutterwave, etc.)
- Payment links generation
- Mobile top-up service
- Two-factor authentication
- Mobile application (Flutter)
- Multi-language support

## 📖 Additional Resources

- [API Documentation](docs/en/OPERATION-MANUAL.md#api-documentation)
- [Developer Portal](qrpay-documentations.html)

## 📝 License

MIT License - see LICENSE file for details
