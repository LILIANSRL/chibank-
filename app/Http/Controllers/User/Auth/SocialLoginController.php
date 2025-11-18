<?php

namespace App\Http\Controllers\User\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\User\LoggedInUsers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Exception;

class SocialLoginController extends Controller
{
    use LoggedInUsers;

    /**
     * Redirect the user to the OAuth Provider.
     *
     * @param string $provider
     * @return \Illuminate\Http\Response
     */
    public function redirectToProvider($provider)
    {
        try {
            // Validate provider
            if (!in_array($provider, ['google', 'facebook', 'alipay', 'wechat'])) {
                return redirect()->route('user.login')->with(['error' => ['Invalid OAuth provider']]);
            }

            // Check if provider is configured
            $config = config("services.{$provider}");
            if (empty($config['client_id']) || empty($config['client_secret'])) {
                return redirect()->route('user.login')->with(['error' => ['OAuth provider not configured. Please contact administrator.']]);
            }

            return Socialite::driver($provider)->redirect();
        } catch (Exception $e) {
            return redirect()->route('user.login')->with(['error' => ['OAuth authentication failed: ' . $e->getMessage()]]);
        }
    }

    /**
     * Obtain the user information from Provider.
     *
     * @param string $provider
     * @return \Illuminate\Http\Response
     */
    public function handleProviderCallback($provider)
    {
        try {
            // Validate provider
            if (!in_array($provider, ['google', 'facebook', 'alipay', 'wechat'])) {
                return redirect()->route('user.login')->with(['error' => ['Invalid OAuth provider']]);
            }

            // Get user from provider
            $socialUser = Socialite::driver($provider)->user();

            // Find or create user
            $user = $this->findOrCreateUser($socialUser, $provider);

            if (!$user) {
                return redirect()->route('user.login')->with(['error' => ['Failed to authenticate with ' . ucfirst($provider)]]);
            }

            // Check if user is banned
            if ($user->status == false) {
                return redirect()->route('user.login')->with(['error' => ['Your account has been banned. Please contact support.']]);
            }

            // Login user
            Auth::guard('web')->login($user);

            // Update user session data
            $user->update([
                'two_factor_verified' => false,
            ]);
            $user->createQr();
            $this->refreshUserWallets($user);
            $this->createLoginLog($user);

            return redirect()->intended(route('user.dashboard'));

        } catch (Exception $e) {
            return redirect()->route('user.login')->with(['error' => ['OAuth authentication failed: ' . $e->getMessage()]]);
        }
    }

    /**
     * Find or create user from social provider
     *
     * @param mixed $socialUser
     * @param string $provider
     * @return User|null
     */
    protected function findOrCreateUser($socialUser, $provider)
    {
        try {
            // Try to find user by oauth provider and id
            $user = User::where('oauth_provider', $provider)
                ->where('oauth_provider_id', $socialUser->getId())
                ->first();

            if ($user) {
                return $user;
            }

            // Try to find user by email
            $user = User::where('email', $socialUser->getEmail())->first();

            if ($user) {
                // Update existing user with OAuth info
                $user->update([
                    'oauth_provider' => $provider,
                    'oauth_provider_id' => $socialUser->getId(),
                ]);
                return $user;
            }

            // Create new user
            $username = $this->generateUniqueUsername($socialUser->getEmail());
            
            $user = User::create([
                'firstname' => $socialUser->getName() ?? 'User',
                'lastname' => '',
                'username' => $username,
                'email' => $socialUser->getEmail(),
                'password' => Hash::make(Str::random(16)), // Random password for OAuth users
                'oauth_provider' => $provider,
                'oauth_provider_id' => $socialUser->getId(),
                'status' => true,
                'email_verified' => true, // OAuth emails are pre-verified
                'sms_verified' => false,
                'kyc_verified' => 0,
                'image' => $this->saveProfileImage($socialUser->getAvatar()),
            ]);

            return $user;

        } catch (Exception $e) {
            // Log error
            logger()->error("Social login error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Generate unique username from email
     *
     * @param string $email
     * @return string
     */
    protected function generateUniqueUsername($email)
    {
        $baseUsername = Str::before($email, '@');
        $baseUsername = Str::slug($baseUsername);
        $username = $baseUsername;
        $counter = 1;

        while (User::where('username', $username)->exists()) {
            $username = $baseUsername . $counter;
            $counter++;
        }

        return $username;
    }

    /**
     * Save profile image from URL
     *
     * @param string|null $avatarUrl
     * @return string|null
     */
    protected function saveProfileImage($avatarUrl)
    {
        // For now, just return the URL
        // In production, you might want to download and store the image locally
        return $avatarUrl;
    }

    /**
     * Handle wallet authentication (MetaMask, WalletConnect, etc.)
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function walletAuth(\Illuminate\Http\Request $request)
    {
        try {
            $request->validate([
                'address' => 'required|string',
                'signature' => 'required|string',
                'message' => 'required|string',
            ]);

            // Verify wallet signature
            $address = $request->address;
            $signature = $request->signature;
            $message = $request->message;

            // Note: In production, you would verify the signature using a library like web3.php
            // For now, we'll do a basic implementation
            
            // Find or create user with wallet address
            $user = User::where('oauth_provider', 'wallet')
                ->where('oauth_provider_id', strtolower($address))
                ->first();

            if (!$user) {
                // Create new user for wallet
                $username = 'wallet_' . Str::substr($address, 0, 8);
                $email = strtolower($address) . '@wallet.local';
                
                $user = User::create([
                    'firstname' => 'Wallet',
                    'lastname' => 'User',
                    'username' => $this->generateUniqueUsername($username),
                    'email' => $email,
                    'password' => Hash::make(Str::random(16)),
                    'oauth_provider' => 'wallet',
                    'oauth_provider_id' => strtolower($address),
                    'status' => true,
                    'email_verified' => false, // Wallet users don't have email verification
                    'sms_verified' => false,
                    'kyc_verified' => 0,
                ]);
            }

            // Check if user is banned
            if ($user->status == false) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account has been banned. Please contact support.'
                ], 403);
            }

            // Login user
            Auth::guard('web')->login($user);

            // Update user session data
            $user->update([
                'two_factor_verified' => false,
            ]);
            $user->createQr();
            $this->refreshUserWallets($user);
            $this->createLoginLog($user);

            return response()->json([
                'success' => true,
                'message' => 'Wallet authentication successful',
                'redirect' => route('user.dashboard')
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Wallet authentication failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
