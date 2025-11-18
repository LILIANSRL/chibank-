@extends('user.layouts.master')

@push('css')
<style>
    .wallet-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 30px;
        border-radius: 8px;
        margin-bottom: 30px;
    }
    .info-box {
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
    }
    .signer-badge {
        display: inline-block;
        padding: 8px 15px;
        background-color: #f0f0f0;
        border-radius: 20px;
        margin: 5px;
    }
    .owner-badge {
        background-color: #ffd700;
        color: #000;
    }
</style>
@endpush

@section('breadcrumb')
    @include('user.components.breadcrumb',['breadcrumbs' => [
        [
            'name'  => __("Dashboard"),
            'url'   => setRoute("user.dashboard"),
        ],
        [
            'name'  => __("Multi-Sig Wallets"),
            'url'   => setRoute("user.multi.sig.wallet.index"),
        ]
    ], 'active' => __($wallet->name)])
@endsection

@section('content')
<div class="dashboard-area mt-10">
    <div class="wallet-header">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h3 class="mb-2">{{ $wallet->name }}</h3>
                <p class="mb-1">
                    <strong>{{ __("Blockchain") }}:</strong> {{ strtoupper($wallet->blockchain) }} | 
                    <strong>{{ __("Currency") }}:</strong> {{ $wallet->currency_code }}
                </p>
                <p class="mb-0">
                    <strong>{{ __("Wallet Type") }}:</strong> {{ ucfirst(str_replace('_', ' ', $wallet->wallet_type)) }}
                </p>
            </div>
            <div class="col-md-4 text-end">
                <h4 class="mb-0">{{ __("Balance") }}</h4>
                <h2 class="mb-0">{{ get_amount($wallet->balance, $wallet->currency_code) }}</h2>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-6 mb-20">
            <div class="info-box">
                <h5 class="mb-3">{{ __("Wallet Information") }}</h5>
                
                <div class="mb-3">
                    <strong>{{ __("Status") }}:</strong>
                    <span class="badge {{ $wallet->status ? 'badge-success' : 'badge-danger' }}">
                        {{ $wallet->status ? __('Active') : __('Inactive') }}
                    </span>
                </div>

                <div class="mb-3">
                    <strong>{{ __("Required Signatures") }}:</strong>
                    {{ $wallet->required_signatures }} / {{ $wallet->total_signers }}
                </div>

                <div class="mb-3">
                    <strong>{{ __("Active Signers") }}:</strong>
                    {{ $wallet->activeSigners->count() }}
                </div>

                @if($wallet->address)
                    <div class="mb-3">
                        <strong>{{ __("Wallet Address") }}:</strong>
                        <p class="mb-0 small" style="word-break: break-all;">{{ $wallet->address }}</p>
                        <button class="btn btn-sm btn-outline-primary mt-2" onclick="copyToClipboard('{{ $wallet->address }}')">
                            <i class="las la-copy"></i> {{ __("Copy Address") }}
                        </button>
                    </div>
                @endif

                @if($wallet->contract_address)
                    <div class="mb-3">
                        <strong>{{ __("Contract Address") }}:</strong>
                        <p class="mb-0 small" style="word-break: break-all;">{{ $wallet->contract_address }}</p>
                    </div>
                @endif

                <div class="mb-3">
                    <strong>{{ __("Created") }}:</strong>
                    {{ $wallet->created_at->format('Y-m-d H:i:s') }}
                </div>
            </div>

            <div class="info-box">
                <h5 class="mb-3">{{ __("Quick Actions") }}</h5>
                <div class="row">
                    <div class="col-6 mb-2">
                        <a href="{{ setRoute('user.multi.sig.transaction.create', $wallet->id) }}" class="btn btn-primary w-100">
                            <i class="las la-paper-plane"></i> {{ __("New Transaction") }}
                        </a>
                    </div>
                    <div class="col-6 mb-2">
                        <a href="{{ setRoute('user.multi.sig.wallet.transactions', $wallet->id) }}" class="btn btn-info w-100">
                            <i class="las la-history"></i> {{ __("View History") }}
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-6 mb-20">
            <div class="info-box">
                <h5 class="mb-3">{{ __("Wallet Signers") }} ({{ $wallet->signers->count() }})</h5>

                @forelse($wallet->signers as $signer)
                    <div class="signer-badge {{ $signer->is_owner ? 'owner-badge' : '' }}">
                        <div class="d-flex align-items-center">
                            <div>
                                <strong>{{ $signer->signer_name }}</strong>
                                @if($signer->is_owner)
                                    <span class="badge badge-warning ms-2">{{ __("Owner") }}</span>
                                @endif
                                <br>
                                <small>{{ $signer->signer_email }}</small>
                                <br>
                                <small>
                                    <strong>{{ __("Weight") }}:</strong> {{ $signer->weight }} |
                                    @if($signer->can_initiate)
                                        <span class="text-success">{{ __("Can Initiate") }}</span>
                                    @endif
                                    @if($signer->can_approve)
                                        <span class="text-success">{{ __("Can Approve") }}</span>
                                    @endif
                                </small>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-muted">{{ __("No signers found") }}</p>
                @endforelse
            </div>

            <div class="info-box">
                <h5 class="mb-3">{{ __("Recent Transactions") }}</h5>
                
                @if($wallet->transactions->count() > 0)
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>{{ __("Trx ID") }}</th>
                                    <th>{{ __("Amount") }}</th>
                                    <th>{{ __("Status") }}</th>
                                    <th>{{ __("Date") }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($wallet->transactions->take(5) as $transaction)
                                    <tr>
                                        <td>
                                            <a href="{{ setRoute('user.multi.sig.transaction.show', [$wallet->id, $transaction->id]) }}">
                                                {{ Str::limit($transaction->trx_id, 15) }}
                                            </a>
                                        </td>
                                        <td>{{ get_amount($transaction->amount, $transaction->currency) }}</td>
                                        <td>
                                            <span class="badge badge-{{ $transaction->status == 'executed' ? 'success' : ($transaction->status == 'pending' ? 'warning' : 'danger') }}">
                                                {{ ucfirst($transaction->status) }}
                                            </span>
                                        </td>
                                        <td>{{ $transaction->created_at->format('Y-m-d') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <a href="{{ setRoute('user.multi.sig.wallet.transactions', $wallet->id) }}" class="btn btn-sm btn-outline-primary">
                        {{ __("View All Transactions") }}
                    </a>
                @else
                    <p class="text-muted">{{ __("No transactions yet") }}</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('script')
<script>
    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(function() {
            alert('{{ __("Address copied to clipboard!") }}');
        }, function(err) {
            console.error('Could not copy text: ', err);
        });
    }
</script>
@endpush
