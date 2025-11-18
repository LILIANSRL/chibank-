# New Payment Gateway Integration Guide

## Overview

This document describes the three new payment gateway integrations added to the Chibank platform to provide customers with more payment options globally.

## New Payment Gateways

### 1. Mollie (Europe-focused)

**About Mollie**
- Leading European payment service provider
- Supports 25+ payment methods
- Strong presence in Netherlands, Belgium, Germany
- PSD2 compliant and SCA-ready

**Supported Payment Methods**:
- Credit/Debit Cards (Visa, Mastercard, American Express)
- iDEAL (Netherlands)
- Bancontact (Belgium)
- SEPA Direct Debit
- SOFORT Banking
- PayPal
- Apple Pay
- Klarna
- And many more...

**Supported Currencies**:
EUR, USD, GBP, CHF, PLN, SEK, DKK, NOK, and more

**Integration Details**:
- **Trait File**: `app/Traits/PaymentGateway/MollieTrait.php`
- **Gateway Constant**: `PaymentGatewayConst::MOLLIE`
- **Init Method**: `mollieInit()`

**Required Credentials**:
```json
{
  "api_key": "live_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
}
```

**Test Mode Credentials**:
```json
{
  "api_key": "test_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
}
```

**Getting Started**:
1. Sign up at https://www.mollie.com
2. Complete business verification
3. Navigate to Dashboard → Developers → API keys
4. Copy your Live or Test API key
5. Add gateway credentials in Chibank admin panel

**Test Cards** (in test mode):
- Success: 5555 5555 5555 4444
- Failure: 4111 1111 1111 1111 (with CVC 111)

**Documentation**: https://docs.mollie.com

---

### 2. Square (US, Canada, UK, Australia, Japan)

**About Square**
- Complete payment ecosystem from Square, Inc.
- Popular in retail and e-commerce
- Integrated with Square POS systems
- Strong fraud prevention

**Supported Payment Methods**:
- Credit/Debit Cards (Visa, Mastercard, American Express, Discover, JCB)
- Digital Wallets (Apple Pay, Google Pay)
- ACH Bank Transfers (US only)
- Cash App Pay

**Supported Currencies**:
USD, CAD, GBP, AUD, JPY

**Integration Details**:
- **Trait File**: `app/Traits/PaymentGateway/SquareTrait.php`
- **Gateway Constant**: `PaymentGatewayConst::SQUARE`
- **Init Method**: `squareInit()`

**Required Credentials**:
```json
{
  "access_token": "EAAAxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
  "location_id": "LXXXxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
  "mode": "production"
}
```

**Sandbox Credentials**:
```json
{
  "access_token": "sandbox-sq0idb-xxxxxxxxxxxxxxxxxxxxxxxxxxx",
  "location_id": "sandbox-location-id",
  "mode": "sandbox"
}
```

**Getting Started**:
1. Sign up at https://squareup.com/signup
2. Complete business verification (for production)
3. Go to Developer Dashboard → Applications
4. Create a new application
5. Get your Access Token and Location ID
6. Add gateway credentials in Chibank admin panel

**Test Cards** (sandbox mode):
- Visa: 4111 1111 1111 1111
- Mastercard: 5105 1051 0510 5100
- American Express: 3782 822463 10005
- Discover: 6011 0000 0000 0004

**CVV**: Any 3 digits (4 for Amex)
**Expiry**: Any future date
**ZIP**: Any 5 digits

**Documentation**: https://developer.squareup.com/docs

---

### 3. Authorize.Net (Enterprise Payment Gateway)

**About Authorize.Net**
- Visa-owned payment gateway
- Established in 1996, highly trusted
- Enterprise-grade security
- Extensive reporting and analytics

**Supported Payment Methods**:
- Credit/Debit Cards (Visa, Mastercard, American Express, Discover, JCB, Diners Club)
- eChecks (ACH)
- Apple Pay
- PayPal (through partner integration)

**Supported Currencies**:
USD, CAD, EUR, GBP, AUD, and 100+ others

**Integration Details**:
- **Trait File**: `app/Traits/PaymentGateway/AuthorizeNetTrait.php`
- **Gateway Constant**: `PaymentGatewayConst::AUTHORIZENET`
- **Init Method**: `authorizeNetInit()`

**Required Credentials**:
```json
{
  "api_login_id": "your_api_login_id",
  "transaction_key": "your_transaction_key",
  "mode": "production"
}
```

**Sandbox Credentials**:
```json
{
  "api_login_id": "sandbox_api_login",
  "transaction_key": "sandbox_transaction_key",
  "mode": "sandbox"
}
```

**Getting Started**:
1. Sign up at https://www.authorize.net
2. Complete merchant account setup
3. Navigate to Account → Settings → API Credentials & Keys
4. Generate new Transaction Key
5. Note your API Login ID
6. Add gateway credentials in Chibank admin panel

**Test Cards** (sandbox mode):
- Visa: 4007000000027
- Mastercard: 5424000000000015
- American Express: 370000000000002
- Discover: 6011000000000012

**CVV**: Any 3 digits (4 for Amex)
**Expiry**: Any future date

**Documentation**: https://developer.authorize.net/api/reference

---

## Installation Instructions

### PHP Package Requirements

The new payment gateways require additional PHP packages. Install them via Composer:

```bash
# For Mollie
composer require mollie/mollie-api-php

# For Square
composer require square/square

# For Authorize.Net
composer require authorizenet/authorizenet
```

### Updating Payment Gateway Configuration

1. **Login to Admin Panel**
2. **Navigate to**: Settings → Payment Gateways → Add New Gateway
3. **Select Gateway Type**: Choose from dropdown (Mollie, Square, or Authorize.Net)
4. **Enter Credentials**: Fill in the required fields based on the gateway
5. **Set Currency**: Choose supported currency for the gateway
6. **Enable Gateway**: Toggle to activate
7. **Save Changes**

### Testing the Integration

#### Test Mode Setup:
1. Use test/sandbox credentials for initial testing
2. Enable test mode in gateway settings
3. Perform test transactions using test cards
4. Verify transaction callbacks and webhooks
5. Check transaction logs in admin panel

#### Production Checklist:
- [ ] Real gateway account created and verified
- [ ] Production API credentials obtained
- [ ] SSL certificate installed on server
- [ ] Webhook URLs configured in gateway dashboard
- [ ] Test transactions completed successfully
- [ ] Currency settings verified
- [ ] Error handling tested
- [ ] Refund process tested (if applicable)

---

## Technical Implementation

### Payment Flow

1. **User Initiates Payment**
   - User selects payment gateway
   - Amount and currency validated
   - Transaction reference generated

2. **Gateway Initialization**
   - Appropriate trait method called (`mollieInit()`, `squareInit()`, or `authorizeNetInit()`)
   - Payment session created with gateway
   - Temporary data stored in database

3. **User Redirected**
   - User sent to gateway's payment page
   - Payment information entered
   - Payment processed by gateway

4. **Callback Processing**
   - Gateway sends callback to Chibank
   - Transaction verified and validated
   - User wallet updated
   - Notification sent to user

### Callback URLs

The system automatically generates callback URLs for each gateway:

**Add Money Callbacks**:
- Success: `/user/add-money/{gateway}/payment/success/{reference}`
- Cancel: `/user/add-money/{gateway}/payment/cancel/{reference}`

**Payment Link Callbacks**:
- Success: `/payment-link/{gateway}/payment/success/{reference}`
- Cancel: `/payment-link/{gateway}/payment/cancel/{reference}`

### Database Schema

Transaction data is stored in `temporary_data` table during processing:

```sql
CREATE TABLE temporary_data (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    type VARCHAR(100),
    identifier VARCHAR(255) UNIQUE,
    data TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

---

## Security Considerations

### API Key Protection
- Never commit API keys to version control
- Store in `.env` file or encrypted database
- Use environment-specific keys (dev/staging/production)
- Rotate keys periodically

### Webhook Security
- Verify webhook signatures
- Validate callback source IP (if applicable)
- Use HTTPS for all callbacks
- Implement request throttling

### PCI Compliance
- Never store full card numbers
- Use tokenization where available
- Implement SCA (Strong Customer Authentication) for EU
- Follow gateway-specific security guidelines

### Error Handling
- Log all gateway errors
- Don't expose sensitive information in error messages
- Implement retry logic for temporary failures
- Alert administrators of critical failures

---

## Troubleshooting

### Common Issues

**Mollie**:
- **Issue**: "Invalid API key"
  - **Solution**: Verify you're using the correct key (test vs live)
- **Issue**: "Currency not supported"
  - **Solution**: Check Mollie dashboard for supported currencies

**Square**:
- **Issue**: "Location not found"
  - **Solution**: Verify Location ID is correct for your account
- **Issue**: "Application not authorized"
  - **Solution**: Check OAuth scopes in Square dashboard

**Authorize.Net**:
- **Issue**: "Transaction key is invalid"
  - **Solution**: Regenerate transaction key in Authorize.Net dashboard
- **Issue**: "Gateway timeout"
  - **Solution**: Check network connectivity and Authorize.Net status

### Debug Mode

Enable debug logging in `.env`:

```env
APP_DEBUG=true  # Only for development!
LOG_LEVEL=debug
```

Check logs at: `storage/logs/laravel.log`

---

## Support and Resources

### Mollie
- Dashboard: https://www.mollie.com/dashboard
- Support: https://help.mollie.com
- API Reference: https://docs.mollie.com/reference

### Square
- Dashboard: https://squareup.com/dashboard
- Developer Portal: https://developer.squareup.com
- Support: https://squareup.com/help

### Authorize.Net
- Merchant Interface: https://account.authorize.net
- Developer Center: https://developer.authorize.net
- Support: 1-877-447-3938 (US)

---

## Migration from Other Gateways

If you're switching from another payment gateway:

1. **Export Transaction History**: Download reports from old gateway
2. **Update Gateway Settings**: Add new gateway credentials
3. **Test Thoroughly**: Run test transactions
4. **Parallel Operation**: Run both gateways temporarily
5. **Monitor Closely**: Watch for any issues during transition
6. **Complete Migration**: Disable old gateway once confident

---

## Updates and Maintenance

### Keeping Up to Date

**Check for Updates**:
- Monitor gateway SDK releases
- Update Composer packages regularly
- Review API changelog from providers

**Update Process**:
```bash
# Update payment gateway packages
composer update mollie/mollie-api-php
composer update square/square
composer update authorizenet/authorizenet

# Clear caches
php artisan cache:clear
php artisan config:clear
```

### Deprecation Notices

Stay informed about:
- API version deprecations
- SSL/TLS requirement changes
- Compliance updates (PSD2, SCA, etc.)
- Security patches

---

## Conclusion

The addition of Mollie, Square, and Authorize.Net expands Chibank's global reach and provides customers with familiar, trusted payment options. Each gateway has been implemented following Laravel best practices and includes comprehensive error handling and security measures.

For questions or issues, please contact the development team or refer to the official documentation of each payment provider.

---

**Last Updated**: 2025-11-18
**Version**: 1.0.0
**Status**: Production Ready
