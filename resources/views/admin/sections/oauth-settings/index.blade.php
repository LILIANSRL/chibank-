@extends('admin.layouts.master')

@push('css')
@endpush

@section('page-title')
    @include('admin.components.page-title',['title' => __($page_title)])
@endsection

@section('breadcrumb')
    @include('admin.components.breadcrumb',['breadcrumbs' => [
        [
            'name'  => __("Dashboard"),
            'url'   => setRoute("admin.dashboard"),
        ]
    ], 'active' => __("OAuth Settings")])
@endsection

@section('content')
    <div class="custom-card">
        <div class="card-header">
            <h6 class="title">{{ __("OAuth & Social Login Settings") }}</h6>
        </div>
        <div class="card-body">
            <form class="card-form" method="POST" action="{{ setRoute('admin.oauth.settings.update') }}">
                @csrf
                @method("PUT")
                
                {{-- Google OAuth --}}
                <div class="row mb-4">
                    <div class="col-12">
                        <h5 class="mb-3"><i class="fab fa-google"></i> {{ __("Google OAuth") }}</h5>
                    </div>
                    <div class="col-xl-4 col-lg-4 form-group">
                        @include('admin.components.form.input',[
                            'label'         => __("Client ID"),
                            'type'          => "text",
                            'class'         => "form--control",
                            'placeholder'   => __("Enter Google Client ID"),
                            'name'          => "google_client_id",
                            'value'         => old('google_client_id', $oauth_settings['google']['client_id']),
                        ])
                    </div>
                    <div class="col-xl-4 col-lg-4 form-group">
                        @include('admin.components.form.input',[
                            'label'         => __("Client Secret"),
                            'type'          => "text",
                            'class'         => "form--control",
                            'placeholder'   => __("Enter Google Client Secret"),
                            'name'          => "google_client_secret",
                            'value'         => old('google_client_secret', $oauth_settings['google']['client_secret']),
                        ])
                    </div>
                    <div class="col-xl-4 col-lg-4 form-group">
                        @include('admin.components.form.input',[
                            'label'         => __("Callback URL"),
                            'type'          => "text",
                            'class'         => "form--control",
                            'placeholder'   => __("Enter Google Callback URL"),
                            'name'          => "google_callback",
                            'value'         => old('google_callback', $oauth_settings['google']['callback']),
                        ])
                    </div>
                </div>

                <hr class="my-4">

                {{-- Facebook OAuth --}}
                <div class="row mb-4">
                    <div class="col-12">
                        <h5 class="mb-3"><i class="fab fa-facebook"></i> {{ __("Facebook OAuth") }}</h5>
                    </div>
                    <div class="col-xl-4 col-lg-4 form-group">
                        @include('admin.components.form.input',[
                            'label'         => __("Client ID"),
                            'type'          => "text",
                            'class'         => "form--control",
                            'placeholder'   => __("Enter Facebook Client ID"),
                            'name'          => "facebook_client_id",
                            'value'         => old('facebook_client_id', $oauth_settings['facebook']['client_id']),
                        ])
                    </div>
                    <div class="col-xl-4 col-lg-4 form-group">
                        @include('admin.components.form.input',[
                            'label'         => __("Client Secret"),
                            'type'          => "text",
                            'class'         => "form--control",
                            'placeholder'   => __("Enter Facebook Client Secret"),
                            'name'          => "facebook_client_secret",
                            'value'         => old('facebook_client_secret', $oauth_settings['facebook']['client_secret']),
                        ])
                    </div>
                    <div class="col-xl-4 col-lg-4 form-group">
                        @include('admin.components.form.input',[
                            'label'         => __("Callback URL"),
                            'type'          => "text",
                            'class'         => "form--control",
                            'placeholder'   => __("Enter Facebook Callback URL"),
                            'name'          => "facebook_callback",
                            'value'         => old('facebook_callback', $oauth_settings['facebook']['callback']),
                        ])
                    </div>
                </div>

                <hr class="my-4">

                {{-- Alipay OAuth --}}
                <div class="row mb-4">
                    <div class="col-12">
                        <h5 class="mb-3"><i class="fab fa-alipay"></i> {{ __("Alipay OAuth") }}</h5>
                    </div>
                    <div class="col-xl-3 col-lg-3 form-group">
                        @include('admin.components.form.input',[
                            'label'         => __("App ID"),
                            'type'          => "text",
                            'class'         => "form--control",
                            'placeholder'   => __("Enter Alipay App ID"),
                            'name'          => "alipay_app_id",
                            'value'         => old('alipay_app_id', $oauth_settings['alipay']['app_id']),
                        ])
                    </div>
                    <div class="col-xl-3 col-lg-3 form-group">
                        @include('admin.components.form.input',[
                            'label'         => __("Private Key"),
                            'type'          => "text",
                            'class'         => "form--control",
                            'placeholder'   => __("Enter Alipay Private Key"),
                            'name'          => "alipay_private_key",
                            'value'         => old('alipay_private_key', $oauth_settings['alipay']['private_key']),
                        ])
                    </div>
                    <div class="col-xl-3 col-lg-3 form-group">
                        @include('admin.components.form.input',[
                            'label'         => __("Public Key"),
                            'type'          => "text",
                            'class'         => "form--control",
                            'placeholder'   => __("Enter Alipay Public Key"),
                            'name'          => "alipay_public_key",
                            'value'         => old('alipay_public_key', $oauth_settings['alipay']['public_key']),
                        ])
                    </div>
                    <div class="col-xl-3 col-lg-3 form-group">
                        @include('admin.components.form.input',[
                            'label'         => __("Callback URL"),
                            'type'          => "text",
                            'class'         => "form--control",
                            'placeholder'   => __("Enter Alipay Callback URL"),
                            'name'          => "alipay_callback",
                            'value'         => old('alipay_callback', $oauth_settings['alipay']['callback']),
                        ])
                    </div>
                </div>

                <hr class="my-4">

                {{-- WeChat OAuth --}}
                <div class="row mb-4">
                    <div class="col-12">
                        <h5 class="mb-3"><i class="fab fa-weixin"></i> {{ __("WeChat OAuth") }}</h5>
                    </div>
                    <div class="col-xl-4 col-lg-4 form-group">
                        @include('admin.components.form.input',[
                            'label'         => __("App ID"),
                            'type'          => "text",
                            'class'         => "form--control",
                            'placeholder'   => __("Enter WeChat App ID"),
                            'name'          => "wechat_app_id",
                            'value'         => old('wechat_app_id', $oauth_settings['wechat']['app_id']),
                        ])
                    </div>
                    <div class="col-xl-4 col-lg-4 form-group">
                        @include('admin.components.form.input',[
                            'label'         => __("App Secret"),
                            'type'          => "text",
                            'class'         => "form--control",
                            'placeholder'   => __("Enter WeChat App Secret"),
                            'name'          => "wechat_app_secret",
                            'value'         => old('wechat_app_secret', $oauth_settings['wechat']['app_secret']),
                        ])
                    </div>
                    <div class="col-xl-4 col-lg-4 form-group">
                        @include('admin.components.form.input',[
                            'label'         => __("Callback URL"),
                            'type'          => "text",
                            'class'         => "form--control",
                            'placeholder'   => __("Enter WeChat Callback URL"),
                            'name'          => "wechat_callback",
                            'value'         => old('wechat_callback', $oauth_settings['wechat']['callback']),
                        ])
                    </div>
                </div>

                <hr class="my-4">

                {{-- Wallet Settings --}}
                <div class="row mb-4">
                    <div class="col-12">
                        <h5 class="mb-3"><i class="fas fa-wallet"></i> {{ __("Wallet Login Settings") }}</h5>
                    </div>
                    <div class="col-xl-3 col-lg-3 form-group">
                        <label>{{ __("Enable MetaMask") }}</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="wallet_metamask_enabled" value="1" 
                                {{ old('wallet_metamask_enabled', $oauth_settings['wallet']['metamask_enabled']) ? 'checked' : '' }}>
                        </div>
                    </div>
                    <div class="col-xl-3 col-lg-3 form-group">
                        <label>{{ __("Enable WalletConnect") }}</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="wallet_walletconnect_enabled" value="1" 
                                {{ old('wallet_walletconnect_enabled', $oauth_settings['wallet']['walletconnect_enabled']) ? 'checked' : '' }}>
                        </div>
                    </div>
                    <div class="col-xl-3 col-lg-3 form-group">
                        @include('admin.components.form.input',[
                            'label'         => __("WalletConnect Project ID"),
                            'type'          => "text",
                            'class'         => "form--control",
                            'placeholder'   => __("Enter Project ID"),
                            'name'          => "walletconnect_project_id",
                            'value'         => old('walletconnect_project_id', $oauth_settings['wallet']['walletconnect_project_id']),
                        ])
                    </div>
                    <div class="col-xl-3 col-lg-3 form-group">
                        <label>{{ __("Enable Trust Wallet") }}</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="wallet_trust_enabled" value="1" 
                                {{ old('wallet_trust_enabled', $oauth_settings['wallet']['trust_enabled']) ? 'checked' : '' }}>
                        </div>
                    </div>
                </div>

                <div class="col-xl-12 col-lg-12">
                    <button type="submit" class="btn--base w-100 btn-loading">{{ __("Update") }}</button>
                </div>
            </form>
        </div>
    </div>

    <div class="custom-card mt-15">
        <div class="card-header">
            <h6 class="title">{{ __("Important Notes") }}</h6>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <h6><strong>{{ __("Setup Instructions:") }}</strong></h6>
                <ul>
                    <li>{{ __("For Google: Create OAuth 2.0 credentials in Google Cloud Console") }}</li>
                    <li>{{ __("For Facebook: Create an app in Facebook Developers portal") }}</li>
                    <li>{{ __("For Alipay: Register your app in Alipay Open Platform") }}</li>
                    <li>{{ __("For WeChat: Register your app in WeChat Open Platform") }}</li>
                    <li>{{ __("For Wallet Login: Enable the desired wallet providers above") }}</li>
                </ul>
                <p class="mt-3"><strong>{{ __("Note:") }}</strong> {{ __("After updating OAuth credentials, users will be able to login using these providers on the login page.") }}</p>
            </div>
        </div>
    </div>
@endsection

@push('script')
@endpush
