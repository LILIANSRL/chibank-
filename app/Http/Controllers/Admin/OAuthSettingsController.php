<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Artisan;

class OAuthSettingsController extends Controller
{
    /**
     * Display OAuth settings page
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $page_title = "OAuth Settings";
        
        // Get current OAuth configurations
        $oauth_settings = [
            'google' => [
                'client_id' => env('GOOGLE_CLIENT_ID', ''),
                'client_secret' => env('GOOGLE_CLIENT_SECRET', ''),
                'callback' => env('GOOGLE_CALLBACK', ''),
            ],
            'facebook' => [
                'client_id' => env('FACEBOOK_CLIENT_ID', ''),
                'client_secret' => env('FACEBOOK_CLIENT_SECRET', ''),
                'callback' => env('FACEBOOK_CALLBACK', ''),
            ],
            'alipay' => [
                'app_id' => env('ALIPAY_APP_ID', ''),
                'private_key' => env('ALIPAY_PRIVATE_KEY', ''),
                'public_key' => env('ALIPAY_PUBLIC_KEY', ''),
                'callback' => env('ALIPAY_CALLBACK', ''),
            ],
            'wechat' => [
                'app_id' => env('WECHAT_APP_ID', ''),
                'app_secret' => env('WECHAT_APP_SECRET', ''),
                'callback' => env('WECHAT_CALLBACK', ''),
            ],
            'wallet' => [
                'metamask_enabled' => env('WALLET_METAMASK_ENABLED', false),
                'walletconnect_enabled' => env('WALLET_WALLETCONNECT_ENABLED', false),
                'walletconnect_project_id' => env('WALLETCONNECT_PROJECT_ID', ''),
                'trust_enabled' => env('WALLET_TRUST_ENABLED', false),
            ],
        ];

        return view('admin.sections.oauth-settings.index', compact('page_title', 'oauth_settings'));
    }

    /**
     * Update OAuth settings
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            // Google
            'google_client_id' => 'nullable|string',
            'google_client_secret' => 'nullable|string',
            'google_callback' => 'nullable|url',
            
            // Facebook
            'facebook_client_id' => 'nullable|string',
            'facebook_client_secret' => 'nullable|string',
            'facebook_callback' => 'nullable|url',
            
            // Alipay
            'alipay_app_id' => 'nullable|string',
            'alipay_private_key' => 'nullable|string',
            'alipay_public_key' => 'nullable|string',
            'alipay_callback' => 'nullable|url',
            
            // WeChat
            'wechat_app_id' => 'nullable|string',
            'wechat_app_secret' => 'nullable|string',
            'wechat_callback' => 'nullable|url',
            
            // Wallet
            'wallet_metamask_enabled' => 'nullable|boolean',
            'wallet_walletconnect_enabled' => 'nullable|boolean',
            'walletconnect_project_id' => 'nullable|string',
            'wallet_trust_enabled' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $validated = $validator->validated();

        // Update .env file
        $this->updateEnvFile([
            'GOOGLE_CLIENT_ID' => $validated['google_client_id'] ?? '',
            'GOOGLE_CLIENT_SECRET' => $validated['google_client_secret'] ?? '',
            'GOOGLE_CALLBACK' => $validated['google_callback'] ?? '',
            
            'FACEBOOK_CLIENT_ID' => $validated['facebook_client_id'] ?? '',
            'FACEBOOK_CLIENT_SECRET' => $validated['facebook_client_secret'] ?? '',
            'FACEBOOK_CALLBACK' => $validated['facebook_callback'] ?? '',
            
            'ALIPAY_APP_ID' => $validated['alipay_app_id'] ?? '',
            'ALIPAY_PRIVATE_KEY' => $validated['alipay_private_key'] ?? '',
            'ALIPAY_PUBLIC_KEY' => $validated['alipay_public_key'] ?? '',
            'ALIPAY_CALLBACK' => $validated['alipay_callback'] ?? '',
            
            'WECHAT_APP_ID' => $validated['wechat_app_id'] ?? '',
            'WECHAT_APP_SECRET' => $validated['wechat_app_secret'] ?? '',
            'WECHAT_CALLBACK' => $validated['wechat_callback'] ?? '',
            
            'WALLET_METAMASK_ENABLED' => $validated['wallet_metamask_enabled'] ? 'true' : 'false',
            'WALLET_WALLETCONNECT_ENABLED' => $validated['wallet_walletconnect_enabled'] ? 'true' : 'false',
            'WALLETCONNECT_PROJECT_ID' => $validated['walletconnect_project_id'] ?? '',
            'WALLET_TRUST_ENABLED' => $validated['wallet_trust_enabled'] ? 'true' : 'false',
        ]);

        // Clear config cache
        try {
            Artisan::call('config:clear');
        } catch (\Exception $e) {
            // Ignore if artisan command fails
        }

        return back()->with(['success' => ['OAuth settings updated successfully!']]);
    }

    /**
     * Update .env file with new values
     *
     * @param array $data
     * @return void
     */
    protected function updateEnvFile(array $data)
    {
        $envFile = base_path('.env');
        
        if (!file_exists($envFile)) {
            return;
        }

        $envContent = file_get_contents($envFile);

        foreach ($data as $key => $value) {
            // Escape special characters in value
            $value = str_replace('"', '\"', $value);
            
            // Check if key exists in .env
            if (preg_match("/^{$key}=.*/m", $envContent)) {
                // Update existing key
                $envContent = preg_replace(
                    "/^{$key}=.*/m",
                    "{$key}=\"{$value}\"",
                    $envContent
                );
            } else {
                // Add new key
                $envContent .= "\n{$key}=\"{$value}\"";
            }
        }

        file_put_contents($envFile, $envContent);
    }
}
