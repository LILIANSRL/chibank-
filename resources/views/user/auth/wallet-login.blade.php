<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __("Wallet Login") }} - {{ $basic_settings->site_name }}</title>
    <link rel="stylesheet" href="{{ asset('public/frontend/css/bootstrap.min.css') }}">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Arial', sans-serif;
        }
        .wallet-login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px;
            max-width: 500px;
            width: 100%;
        }
        .wallet-logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .wallet-logo img {
            max-width: 150px;
        }
        .wallet-btn {
            width: 100%;
            padding: 15px;
            margin: 10px 0;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            background: white;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .wallet-btn:hover {
            border-color: #667eea;
            background: #f8f9ff;
            transform: translateY(-2px);
        }
        .wallet-icon {
            width: 40px;
            height: 40px;
            background: #f0f0f0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        .traditional-login {
            text-align: center;
            margin-top: 30px;
            padding-top: 30px;
            border-top: 1px solid #e0e0e0;
        }
        #wallet-connect-modal .modal-content {
            border-radius: 15px;
        }
        .connecting-spinner {
            display: none;
            text-align: center;
            padding: 20px;
        }
    </style>
</head>
<body>
    <div class="wallet-login-container">
        <div class="wallet-logo">
            <img src="{{ get_logo($basic_settings) }}" alt="{{ $basic_settings->site_name }}">
            <h3 class="mt-3">{{ __("Connect Your Wallet") }}</h3>
            <p class="text-muted">{{ __("Choose your preferred wallet to continue") }}</p>
        </div>

        <div id="wallet-options">
            @foreach($supported_providers as $key => $name)
                <button class="wallet-btn" data-provider="{{ $key }}" onclick="connectWallet('{{ $key }}')">
                    <div class="d-flex align-items-center">
                        <div class="wallet-icon">
                            <i class="las la-wallet"></i>
                        </div>
                        <span class="ms-3"><strong>{{ $name }}</strong></span>
                    </div>
                    <i class="las la-arrow-right"></i>
                </button>
            @endforeach
        </div>

        <div id="connecting-state" class="connecting-spinner">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">{{ __("Connecting...") }}</span>
            </div>
            <p class="mt-3">{{ __("Connecting to your wallet...") }}</p>
            <p class="text-muted small">{{ __("Please approve the connection in your wallet") }}</p>
        </div>

        <div class="traditional-login">
            <p class="text-muted">{{ __("Don't have a wallet?") }}</p>
            <a href="{{ setRoute('user.login') }}" class="btn btn-outline-primary">
                {{ __("Use Email & Password") }}
            </a>
        </div>
    </div>

    <!-- Web3.js or Ethers.js would be loaded here -->
    <script src="{{ asset('public/frontend/js/jquery-3.6.0.min.js') }}"></script>
    <script src="{{ asset('public/frontend/js/bootstrap.bundle.min.js') }}"></script>
    <script>
        async function connectWallet(provider) {
            console.log('Connecting to:', provider);
            
            // Show connecting state
            $('#wallet-options').hide();
            $('#connecting-state').show();

            try {
                if (provider === 'metamask') {
                    await connectMetaMask();
                } else if (provider === 'walletconnect') {
                    await connectWalletConnect();
                } else {
                    alert('{{ __("This wallet is not yet integrated. Please use MetaMask or traditional login.") }}');
                    resetConnectionState();
                }
            } catch (error) {
                console.error('Connection error:', error);
                alert('{{ __("Failed to connect wallet. Please try again.") }}');
                resetConnectionState();
            }
        }

        async function connectMetaMask() {
            if (typeof window.ethereum === 'undefined') {
                alert('{{ __("MetaMask is not installed. Please install MetaMask browser extension.") }}');
                window.open('https://metamask.io/download/', '_blank');
                resetConnectionState();
                return;
            }

            try {
                // Request account access
                const accounts = await window.ethereum.request({ method: 'eth_requestAccounts' });
                const walletAddress = accounts[0];
                const chainId = await window.ethereum.request({ method: 'eth_chainId' });

                // Request nonce from server
                const nonceResponse = await fetch('{{ setRoute("user.wallet.login.request.nonce") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        wallet_address: walletAddress,
                        blockchain: 'ethereum',
                        wallet_provider: 'metamask'
                    })
                });

                const nonceData = await nonceResponse.json();
                
                if (!nonceData.success) {
                    throw new Error(nonceData.error || 'Failed to get nonce');
                }

                // Request signature
                const message = nonceData.message;
                const signature = await window.ethereum.request({
                    method: 'personal_sign',
                    params: [message, walletAddress]
                });

                // Verify signature and login
                const loginResponse = await fetch('{{ setRoute("user.wallet.login.verify.signature") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        wallet_address: walletAddress,
                        blockchain: 'ethereum',
                        signature: signature,
                        wallet_provider: 'metamask'
                    })
                });

                const loginData = await loginResponse.json();

                if (loginData.success) {
                    // Redirect to dashboard
                    window.location.href = loginData.redirect;
                } else {
                    throw new Error(loginData.error || 'Login failed');
                }

            } catch (error) {
                console.error('MetaMask error:', error);
                throw error;
            }
        }

        async function connectWalletConnect() {
            alert('{{ __("WalletConnect integration coming soon. Please use MetaMask or traditional login.") }}');
            resetConnectionState();
        }

        function resetConnectionState() {
            $('#wallet-options').show();
            $('#connecting-state').hide();
        }

        // Check if MetaMask is installed
        $(document).ready(function() {
            if (typeof window.ethereum !== 'undefined') {
                console.log('MetaMask is installed!');
            }
        });
    </script>
</body>
</html>
