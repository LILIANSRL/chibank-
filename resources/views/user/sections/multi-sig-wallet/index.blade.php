@extends('user.layouts.master')

@push('css')
<style>
    .wallet-card {
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
        transition: all 0.3s;
    }
    .wallet-card:hover {
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    .chain-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 600;
        margin-right: 8px;
    }
    .status-badge {
        padding: 4px 12px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 600;
    }
    .status-active {
        background-color: #d4edda;
        color: #155724;
    }
    .status-inactive {
        background-color: #f8d7da;
        color: #721c24;
    }
</style>
@endpush

@section('breadcrumb')
    @include('user.components.breadcrumb',['breadcrumbs' => [
        [
            'name'  => __("Dashboard"),
            'url'   => setRoute("user.dashboard"),
        ]
    ], 'active' => __("Multi-Signature Wallets")])
@endsection

@section('content')
<div class="dashboard-area mt-10">
    <div class="dashboard-header-wrapper">
        <h4 class="title">{{ __("Multi-Signature Wallets") }}</h4>
        <div class="dashboard-btn-wrapper">
            <div class="dashboard-btn">
                <a href="{{ setRoute('user.multi.sig.wallet.create') }}" class="btn--base">
                    <i class="las la-plus"></i> {{ __("Create Wallet") }}
                </a>
            </div>
        </div>
    </div>

    <div class="row mt-20 mb-20-none">
        @forelse($wallets as $wallet)
            <div class="col-xl-6 col-lg-6 mb-20">
                <div class="wallet-card">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h5 class="mb-1">{{ $wallet->name }}</h5>
                            <span class="chain-badge" style="background-color: #e3f2fd; color: #1976d2;">
                                {{ strtoupper($wallet->blockchain) }}
                            </span>
                            <span class="chain-badge" style="background-color: #fff3e0; color: #f57c00;">
                                {{ $wallet->currency_code }}
                            </span>
                        </div>
                        <span class="status-badge {{ $wallet->status ? 'status-active' : 'status-inactive' }}">
                            {{ $wallet->status ? __('Active') : __('Inactive') }}
                        </span>
                    </div>

                    <div class="wallet-info mb-3">
                        <div class="row">
                            <div class="col-6 mb-2">
                                <small class="text-muted">{{ __("Balance") }}</small>
                                <h6 class="mb-0">{{ get_amount($wallet->balance, $wallet->currency_code) }}</h6>
                            </div>
                            <div class="col-6 mb-2">
                                <small class="text-muted">{{ __("Wallet Type") }}</small>
                                <h6 class="mb-0">{{ ucfirst(str_replace('_', ' ', $wallet->wallet_type)) }}</h6>
                            </div>
                            <div class="col-6 mb-2">
                                <small class="text-muted">{{ __("Required Signatures") }}</small>
                                <h6 class="mb-0">{{ $wallet->required_signatures }} / {{ $wallet->total_signers }}</h6>
                            </div>
                            <div class="col-6 mb-2">
                                <small class="text-muted">{{ __("Active Signers") }}</small>
                                <h6 class="mb-0">{{ $wallet->activeSigners->count() }}</h6>
                            </div>
                        </div>

                        @if($wallet->address)
                            <div class="mt-2">
                                <small class="text-muted">{{ __("Address") }}</small>
                                <p class="mb-0 small" style="word-break: break-all;">{{ Str::limit($wallet->address, 40) }}</p>
                            </div>
                        @endif
                    </div>

                    <div class="d-flex justify-content-between mt-3">
                        <a href="{{ setRoute('user.multi.sig.wallet.show', $wallet->id) }}" class="btn btn-sm btn-outline-primary">
                            <i class="las la-eye"></i> {{ __("View Details") }}
                        </a>
                        <a href="{{ setRoute('user.multi.sig.wallet.transactions', $wallet->id) }}" class="btn btn-sm btn-outline-info">
                            <i class="las la-exchange-alt"></i> {{ __("Transactions") }}
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-primary text-center">
                    <i class="las la-info-circle" style="font-size: 48px;"></i>
                    <h5 class="mt-2">{{ __("No wallets found") }}</h5>
                    <p>{{ __("Create your first multi-signature wallet to get started") }}</p>
                    <a href="{{ setRoute('user.multi.sig.wallet.create') }}" class="btn--base mt-2">
                        <i class="las la-plus"></i> {{ __("Create Wallet") }}
                    </a>
                </div>
            </div>
        @endforelse
    </div>

    <div class="row">
        <div class="col-12">
            {{ $wallets->links() }}
        </div>
    </div>

    @if($supported_chains)
        <div class="row mt-30">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">{{ __("Supported Blockchain Networks") }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            @foreach($supported_chains as $chain)
                                <div class="col-md-3 col-sm-4 col-6 mb-2">
                                    <span class="chain-badge" style="background-color: #f5f5f5; color: #333;">
                                        {{ strtoupper($chain['coin']) }} - {{ ucfirst($chain['chain']) }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

@push('script')
<script>
    // Add any JavaScript functionality here
</script>
@endpush
