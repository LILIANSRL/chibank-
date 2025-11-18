# Multi-Signature Wallet & Wallet Authentication Feature

## Overview

This implementation adds comprehensive multi-signature wallet support and blockchain wallet authentication to the Chibank platform, supporting 13+ blockchain networks.

## Features

### 1. Multi-Signature Wallets
- Create and manage multi-signature wallets across multiple blockchains
- Configurable signature requirements (M-of-N approval scheme)
- Support for weighted signatures
- Comprehensive signer management with granular permissions

### 2. Supported Blockchain Networks (13 Chains)

1. **Ethereum (ETH)** - Testnet: Sepolia
2. **Bitcoin (BTC)** - Mainnet only
3. **Tether (USDT)** - Tron network
4. **Solana (SOL)** - Testnet: Devnet
5. **Polygon (MATIC)** - Testnet: Mumbai
6. **Binance Smart Chain (BNB)** - Testnet available
7. **Avalanche (AVAX)** - Testnet available
8. **Cardano (ADA)** - Mainnet
9. **Polkadot (DOT)** - Mainnet
10. **Litecoin (LTC)** - Testnet available
11. **Ripple (XRP)** - Testnet available
12. **Tron (TRX)** - Testnet: Shasta
13. **Dogecoin (DOGE)** - Testnet available

### 3. Wallet Authentication
- Login using cryptocurrency wallets (MetaMask, WalletConnect, etc.)
- Sign-in with Ethereum (SIWE) compatible
- Link multiple wallets to a single account
- Support for 6+ wallet providers:
  - MetaMask
  - WalletConnect
  - Coinbase Wallet
  - Trust Wallet
  - Phantom
  - Binance Chain Wallet

## Database Structure

### Tables Created

1. **multi_sig_wallets** - Main wallet storage
2. **wallet_signers** - Authorized signers for each wallet
3. **multi_sig_transactions** - Transaction records requiring approval
4. **transaction_approvals** - Individual approval/rejection records
5. **wallet_authentications** - Linked wallet addresses for authentication

## API Endpoints

### Multi-Signature Wallet Management

```
GET    /user/multi-sig-wallet              - List all wallets
GET    /user/multi-sig-wallet/create       - Create wallet form
POST   /user/multi-sig-wallet/store        - Store new wallet
GET    /user/multi-sig-wallet/{id}         - Wallet details
GET    /user/multi-sig-wallet/{id}/transactions - Wallet transactions
POST   /user/multi-sig-wallet/{id}/update-status - Toggle wallet status
DELETE /user/multi-sig-wallet/{id}         - Delete wallet
```

### Multi-Signature Transactions

```
GET    /user/multi-sig-wallet/{walletId}/transaction/create - New transaction form
POST   /user/multi-sig-wallet/{walletId}/transaction/store  - Create transaction
GET    /user/multi-sig-wallet/{walletId}/transaction/{id}   - Transaction details
POST   /user/multi-sig-wallet/{walletId}/transaction/{id}/approve - Approve transaction
POST   /user/multi-sig-wallet/{walletId}/transaction/{id}/reject  - Reject transaction
POST   /user/multi-sig-wallet/{walletId}/transaction/{id}/execute - Execute transaction
```

### Wallet Authentication

```
GET    /wallet-login                    - Wallet login page
POST   /wallet-login/request-nonce      - Request signature nonce
POST   /wallet-login/verify-signature   - Verify and login

GET    /user/wallet-auth/linked-wallets - Get linked wallets (authenticated)
POST   /user/wallet-auth/link           - Link wallet to account
DELETE /user/wallet-auth/unlink/{id}    - Unlink wallet
```

## Usage Guide

### Creating a Multi-Signature Wallet

1. Navigate to Multi-Signature Wallets section
2. Click "Create Wallet"
3. Fill in wallet details:
   - Wallet name
   - Select blockchain network
   - Choose currency
   - Set required signatures (e.g., 2 of 3)
4. Add signers:
   - Enter name and email for each signer
   - Set signature weight (for weighted multi-sig)
   - Configure permissions (can initiate/approve)
5. Submit to create wallet

### Initiating a Transaction

1. Open wallet details
2. Click "New Transaction"
3. Enter transaction details:
   - Recipient address
   - Amount
   - Transaction type
   - Gas/fee settings
4. Submit transaction for approval

### Approving/Rejecting Transactions

1. View pending transactions
2. Review transaction details
3. Click "Approve" or "Reject"
4. Add optional comment
5. Submit approval/rejection

Once required approvals are met, transaction can be executed on blockchain.

### Wallet Login

1. Navigate to `/wallet-login`
2. Select your wallet provider (e.g., MetaMask)
3. Approve connection in wallet
4. Sign the authentication message
5. You'll be logged in automatically

## Security Features

1. **Signature Verification**: All wallet actions require cryptographic signatures
2. **Nonce System**: Time-limited nonces prevent replay attacks
3. **Multi-Factor Approval**: Configurable M-of-N signature requirements
4. **Audit Trail**: Complete history of all approvals and actions
5. **IP & User Agent Tracking**: Monitor wallet access locations
6. **Weighted Signatures**: Support for different signer importance levels

## Configuration

### Enable Multi-Sig Wallets

Multi-signature wallets are enabled by default. No additional configuration needed.

### Configure Blockchain Networks

Edit `app/Traits/PaymentGateway/Tatum.php` to modify supported chains:

```php
public function tatumRegisteredChains($coin = null)
{
    $register_coins = [
        'ETH' => [
            'chain'     => 'ethereum',
            'testnet'   => 'ethereum-sepolia',
            'coin'      => 'ETH',
            'status'    => true,
        ],
        // Add more chains here
    ];
}
```

## Integration with Tatum

This implementation uses Tatum API for blockchain operations. Ensure your Tatum API credentials are configured in the payment gateway settings.

### Required Tatum Features

- Wallet generation
- Address derivation
- Balance checking
- Transaction creation
- Transaction signing

## Frontend Integration

### Web3 Integration

The wallet login page includes MetaMask integration. For full functionality, ensure:

1. Users have MetaMask or compatible wallet installed
2. SSL/TLS is enabled (required for Web3)
3. Proper CORS headers are configured

### Adding More Wallet Providers

To add additional wallet providers, update:

1. `WalletAuthController::showLoginForm()` - Add provider to list
2. `wallet-login.blade.php` - Add connection logic
3. Implement signature verification for new provider

## Best Practices

### For Users

1. **Backup Wallet Keys**: Never lose access to your signing wallet
2. **Verify Addresses**: Always double-check recipient addresses
3. **Review Approvals**: Carefully review all transactions before approving
4. **Use Hardware Wallets**: For high-value wallets, use hardware signers

### For Administrators

1. **Monitor Failed Logins**: Track suspicious wallet connection attempts
2. **Review Large Transactions**: Flag unusual transaction patterns
3. **Backup Private Keys**: Securely store master wallet keys
4. **Update Regularly**: Keep blockchain integrations updated

## Troubleshooting

### Wallet Won't Connect

- Ensure wallet extension is installed and unlocked
- Check browser console for errors
- Verify SSL certificate is valid
- Try different browser or wallet

### Transaction Won't Execute

- Verify all required approvals are collected
- Check wallet has sufficient balance for fees
- Ensure blockchain network is operational
- Review transaction parameters

### Signature Verification Fails

- Check nonce hasn't expired (15 min limit)
- Verify wallet address matches exactly
- Ensure message is signed correctly
- Try requesting new nonce

## Future Enhancements

1. **Smart Contract Integration**: Deploy actual multi-sig contracts
2. **Hardware Wallet Support**: Ledger, Trezor integration
3. **Mobile Wallet Support**: Trust, Coinbase, Rainbow
4. **Cross-Chain Swaps**: Built-in DEX functionality
5. **NFT Support**: Multi-sig NFT management
6. **Advanced Analytics**: Transaction insights and reporting
7. **Batch Transactions**: Execute multiple txns at once
8. **Time-Locked Transactions**: Delayed execution support

## Technical Stack

- **Backend**: Laravel 9.x, PHP 8.0+
- **Frontend**: Blade templates, Bootstrap 5, jQuery
- **Blockchain**: Tatum API integration
- **Web3**: MetaMask, WalletConnect protocols
- **Database**: MySQL/PostgreSQL

## Support

For issues or questions:
- Check the troubleshooting section above
- Review Tatum API documentation
- Contact system administrator
- Submit issue on project repository

---

**Version**: 1.0.0  
**Last Updated**: 2024-11-18  
**Author**: Chibank Development Team
