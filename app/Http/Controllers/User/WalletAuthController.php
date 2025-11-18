<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\WalletAuthentication;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Exception;

class WalletAuthController extends Controller
{
    /**
     * Show wallet login page
     */
    public function showLoginForm()
    {
        $page_title = "Wallet Login";
        $supported_providers = [
            'metamask' => 'MetaMask',
            'walletconnect' => 'WalletConnect',
            'coinbase' => 'Coinbase Wallet',
            'trust' => 'Trust Wallet',
            'phantom' => 'Phantom',
            'binance' => 'Binance Chain Wallet',
        ];

        return view('user.auth.wallet-login', compact('page_title', 'supported_providers'));
    }

    /**
     * Request nonce for wallet signature
     */
    public function requestNonce(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'wallet_address' => 'required|string',
            'blockchain' => 'required|string',
            'wallet_provider' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        try {
            // Find or create wallet authentication record
            $walletAuth = WalletAuthentication::firstOrCreate(
                [
                    'wallet_address' => strtolower($request->wallet_address),
                    'blockchain' => $request->blockchain,
                ],
                [
                    'wallet_provider' => $request->wallet_provider,
                    'status' => true,
                ]
            );

            // Generate new nonce
            $nonce = $walletAuth->generateNonce();

            return response()->json([
                'success' => true,
                'nonce' => $nonce,
                'message' => "Sign this message to verify wallet ownership: {$nonce}"
            ]);

        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to generate nonce: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Verify wallet signature and login
     */
    public function verifySignature(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'wallet_address' => 'required|string',
            'blockchain' => 'required|string',
            'signature' => 'required|string',
            'wallet_provider' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        try {
            $walletAuth = WalletAuthentication::where('wallet_address', strtolower($request->wallet_address))
                ->where('blockchain', $request->blockchain)
                ->first();

            if (!$walletAuth || !$walletAuth->isNonceValid()) {
                return response()->json(['error' => 'Invalid or expired nonce'], 400);
            }

            // TODO: Implement actual signature verification based on blockchain
            // For now, we'll accept the signature as valid
            $signatureValid = true;

            if (!$signatureValid) {
                return response()->json(['error' => 'Invalid signature'], 400);
            }

            // Update wallet authentication
            $walletAuth->update([
                'signature' => $request->signature,
                'is_verified' => true,
                'last_login_at' => now(),
                'login_ip' => $request->ip(),
                'login_user_agent' => $request->userAgent(),
                'wallet_provider' => $request->wallet_provider ?? $walletAuth->wallet_provider,
            ]);

            // Find or create user associated with this wallet
            if ($walletAuth->user_id) {
                $user = User::find($walletAuth->user_id);
            } else {
                // Create a new user account for this wallet
                $username = 'wallet_' . substr($request->wallet_address, 0, 10);
                $user = User::create([
                    'username' => $username,
                    'email' => $username . '@wallet.local',
                    'password' => bcrypt(bin2hex(random_bytes(16))),
                    'email_verified' => 1,
                    'sms_verified' => 0,
                    'kyc_verified' => 0,
                    'status' => 1,
                ]);

                // Link wallet to user
                $walletAuth->update([
                    'user_id' => $user->id,
                    'user_type' => 'user',
                    'is_primary' => true,
                ]);
            }

            // Login user
            Auth::login($user);

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'redirect' => route('user.dashboard')
            ]);

        } catch (Exception $e) {
            return response()->json(['error' => 'Login failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Link wallet to existing account
     */
    public function linkWallet(Request $request)
    {
        if (!auth()->check()) {
            return response()->json(['error' => 'Authentication required'], 401);
        }

        $validator = Validator::make($request->all(), [
            'wallet_address' => 'required|string',
            'blockchain' => 'required|string',
            'signature' => 'required|string',
            'wallet_provider' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        try {
            // Check if wallet is already linked
            $existingWallet = WalletAuthentication::where('wallet_address', strtolower($request->wallet_address))
                ->where('blockchain', $request->blockchain)
                ->first();

            if ($existingWallet && $existingWallet->user_id && $existingWallet->user_id != auth()->id()) {
                return response()->json(['error' => 'Wallet already linked to another account'], 400);
            }

            // Create or update wallet authentication
            $walletAuth = WalletAuthentication::updateOrCreate(
                [
                    'wallet_address' => strtolower($request->wallet_address),
                    'blockchain' => $request->blockchain,
                ],
                [
                    'user_id' => auth()->id(),
                    'user_type' => 'user',
                    'signature' => $request->signature,
                    'wallet_provider' => $request->wallet_provider,
                    'is_verified' => true,
                    'status' => true,
                    'last_login_at' => now(),
                    'login_ip' => $request->ip(),
                    'login_user_agent' => $request->userAgent(),
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Wallet linked successfully'
            ]);

        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to link wallet: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Unlink wallet from account
     */
    public function unlinkWallet(Request $request, $id)
    {
        if (!auth()->check()) {
            return response()->json(['error' => 'Authentication required'], 401);
        }

        try {
            $walletAuth = WalletAuthentication::where('id', $id)
                ->where('user_id', auth()->id())
                ->firstOrFail();

            // Don't allow unlinking primary wallet if it's the only one
            if ($walletAuth->is_primary) {
                $otherWallets = WalletAuthentication::where('user_id', auth()->id())
                    ->where('id', '!=', $id)
                    ->count();

                if ($otherWallets == 0) {
                    return response()->json(['error' => 'Cannot unlink your only wallet'], 400);
                }
            }

            $walletAuth->delete();

            return response()->json([
                'success' => true,
                'message' => 'Wallet unlinked successfully'
            ]);

        } catch (Exception $e) {
            return response()->json(['error' => 'Failed to unlink wallet: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get user's linked wallets
     */
    public function linkedWallets()
    {
        if (!auth()->check()) {
            return response()->json(['error' => 'Authentication required'], 401);
        }

        $wallets = WalletAuthentication::where('user_id', auth()->id())
            ->where('user_type', 'user')
            ->orderByDesc('is_primary')
            ->orderByDesc('last_login_at')
            ->get();

        return response()->json([
            'success' => true,
            'wallets' => $wallets
        ]);
    }
}
