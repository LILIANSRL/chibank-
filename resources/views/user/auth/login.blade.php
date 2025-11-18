
@extends('user.layouts.user_auth')

@php
    $lang = selectedLang();
    $auth_slug = Illuminate\Support\Str::slug(App\Constants\SiteSectionConst::AUTH_SECTION);
    $auth_text = App\Models\Admin\SiteSections::getData( $auth_slug)->first();
@endphp
@section('content')
<!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    Start acount
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
<section class="account">
    <div class="account-area">
        <div class="account-wrapper">
            <div class="account-logo text-center">
                <a href="{{ setRoute('index') }}" class="site-logo">
                    <img src="{{ get_logo($basic_settings) }}"  data-white_img="{{ get_logo($basic_settings,'white') }}"
                            data-dark_img="{{ get_logo($basic_settings,'dark') }}"
                                alt="site-logo">
                </a>
            </div>
            <h5 class="title">{{ __("Log in and Stay Connected") }}</h5>
            <p>{{ __(@$auth_text->value->language->$lang->login_text) }}</p>
            <form class="account-form" action="{{ setRoute('user.login.submit') }}" method="POST">
                @csrf
                <div class="row ml-b-20">
                    <div class="col-xl-12 col-lg-12 form-group">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text copytext"><span>{{ __("Email") }}</span></span>
                            </div>
                             <input type="email" name="credentials" class="form--control" placeholder="{{ __("enter Email Address") }}" required value="{{old('credentials')}}">
                        </div>
                    </div>
                    <div class="col-lg-12 form-group" id="show_hide_password">
                        <input type="password" required class="form-control form--control" name="password"placeholder="{{ __('enter Password') }}">
                        <a href="javascript:void(0)" class="show-pass"><i class="fa fa-eye-slash" aria-hidden="true"></i></a>
                    </div>
                    <div class="col-lg-12 form-group">
                        <div class="forgot-item">
                            <label><a href="{{ setRoute('user.password.forgot') }}">{{ __("Forgot Password") }}?</a></label>
                        </div>
                    </div>
                    <div class="col-lg-12 form-group text-center">
                        <x-security.google-recaptcha-field />
                        <button type="submit" class="btn--base w-100 btn-loading">{{ __("Login Now") }} <i class="las la-arrow-right"></i></button>
                    </div>
                    
                    {{-- Social Login Options --}}
                    <div class="or-area">
                        <span class="or-line"></span>
                        <span class="or-title">{{ __("Or Login With") }}</span>
                        <span class="or-line"></span>
                    </div>
                    
                    <div class="col-lg-12">
                        <div class="social-login-buttons">
                            @if(config('services.google.client_id'))
                            <a href="{{ route('user.social.login', 'google') }}" class="btn btn-social btn-google">
                                <i class="fab fa-google"></i> {{ __("Google") }}
                            </a>
                            @endif
                            
                            @if(config('services.facebook.client_id'))
                            <a href="{{ route('user.social.login', 'facebook') }}" class="btn btn-social btn-facebook">
                                <i class="fab fa-facebook-f"></i> {{ __("Facebook") }}
                            </a>
                            @endif
                            
                            @if(config('services.alipay.client_id'))
                            <a href="{{ route('user.social.login', 'alipay') }}" class="btn btn-social btn-alipay">
                                <i class="fab fa-alipay"></i> {{ __("Alipay") }}
                            </a>
                            @endif
                            
                            @if(config('services.wechat.client_id'))
                            <a href="{{ route('user.social.login', 'wechat') }}" class="btn btn-social btn-wechat">
                                <i class="fab fa-weixin"></i> {{ __("WeChat") }}
                            </a>
                            @endif
                            
                            @if(config('services.wallet.metamask.enabled') || config('services.wallet.walletconnect.enabled') || config('services.wallet.trust_wallet.enabled'))
                            <button type="button" class="btn btn-social btn-wallet" id="walletLoginBtn">
                                <i class="fas fa-wallet"></i> {{ __("Wallet Login") }}
                            </button>
                            @endif
                        </div>
                    </div>
                    
                    @if($basic_settings->user_registration)
                    <div class="or-area">
                        <span class="or-line"></span>
                        <span class="or-title">{{ __("Or") }}</span>
                        <span class="or-line"></span>
                    </div>
                    <div class="col-lg-12 text-center">
                        <div class="account-item">
                            <label>{{ __("Don't Have An Account?") }} <a href="{{ setRoute('user.register') }}" class="account-control-btn">{{ __("Register Now") }}</a></label>
                        </div>
                    </div>
                    @endif
                </div>
            </form>
        </div>
    </div>
</section>
<!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    End acount
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->

<ul class="bg-bubbles">
    <li></li>
    <li></li>
    <li></li>
    <li></li>
    <li></li>
    <li></li>
    <li></li>
    <li></li>
    <li></li>
    <li></li>
</ul>

@endsection

@push('script')
<script>
    $(document).ready(function() {
        $("#show_hide_password a").on('click', function(event) {
            event.preventDefault();
            if($('#show_hide_password input').attr("type") == "text"){
                $('#show_hide_password input').attr('type', 'password');
                $('#show_hide_password i').addClass( "fa-eye-slash" );
                $('#show_hide_password i').removeClass( "fa-eye" );
            }else if($('#show_hide_password input').attr("type") == "password"){
                $('#show_hide_password input').attr('type', 'text');
                $('#show_hide_password i').removeClass( "fa-eye-slash" );
                $('#show_hide_password i').addClass( "fa-eye" );
            }
        });

        // Wallet Login Handler
        $('#walletLoginBtn').on('click', async function(e) {
            e.preventDefault();
            
            try {
                // Check if MetaMask is installed
                if (typeof window.ethereum !== 'undefined') {
                    // Request account access
                    const accounts = await window.ethereum.request({ method: 'eth_requestAccounts' });
                    const address = accounts[0];
                    
                    // Create message to sign
                    const message = "Sign this message to login to {{ config('app.name') }}. Nonce: " + Date.now();
                    
                    // Request signature
                    const signature = await window.ethereum.request({
                        method: 'personal_sign',
                        params: [message, address]
                    });
                    
                    // Send to server for verification
                    $.ajax({
                        url: "{{ route('user.wallet.auth') }}",
                        type: 'POST',
                        data: {
                            _token: "{{ csrf_token() }}",
                            address: address,
                            signature: signature,
                            message: message
                        },
                        success: function(response) {
                            if (response.success) {
                                window.location.href = response.redirect;
                            } else {
                                alert(response.message || 'Authentication failed');
                            }
                        },
                        error: function(xhr) {
                            const response = xhr.responseJSON;
                            alert(response.message || 'Wallet authentication failed');
                        }
                    });
                    
                } else {
                    alert('Please install MetaMask or another Web3 wallet to use this feature');
                }
            } catch (error) {
                console.error('Wallet login error:', error);
                alert('Wallet authentication failed: ' + error.message);
            }
        });
    });
</script>

<style>
    .social-login-buttons {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 10px;
        margin-top: 15px;
    }
    
    .btn-social {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 12px 20px;
        border-radius: 5px;
        font-weight: 500;
        transition: all 0.3s ease;
        border: none;
        color: white;
        text-decoration: none;
    }
    
    .btn-social:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        color: white;
    }
    
    .btn-google {
        background-color: #DB4437;
    }
    
    .btn-google:hover {
        background-color: #C23321;
    }
    
    .btn-facebook {
        background-color: #4267B2;
    }
    
    .btn-facebook:hover {
        background-color: #365899;
    }
    
    .btn-alipay {
        background-color: #1677FF;
    }
    
    .btn-alipay:hover {
        background-color: #0958D9;
    }
    
    .btn-wechat {
        background-color: #07C160;
    }
    
    .btn-wechat:hover {
        background-color: #06AD56;
    }
    
    .btn-wallet {
        background-color: #F0B90B;
        color: #000;
    }
    
    .btn-wallet:hover {
        background-color: #D9A00A;
        color: #000;
    }
    
    @media (max-width: 576px) {
        .social-login-buttons {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush
