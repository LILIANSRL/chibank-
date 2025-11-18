@extends('user.layouts.master')

@push('css')
<style>
    .signer-row {
        border: 1px solid #e0e0e0;
        border-radius: 4px;
        padding: 15px;
        margin-bottom: 15px;
        background-color: #f9f9f9;
    }
    .remove-signer {
        cursor: pointer;
        color: #dc3545;
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
    ], 'active' => __("Create Wallet")])
@endsection

@section('content')
<div class="dashboard-area mt-10">
    <div class="dashboard-header-wrapper">
        <h4 class="title">{{ __("Create Multi-Signature Wallet") }}</h4>
    </div>

    <div class="row mt-20">
        <div class="col-xl-8 col-lg-10 mx-auto">
            <div class="custom-card mt-10">
                <div class="card-body">
                    <form action="{{ setRoute('user.multi.sig.wallet.store') }}" method="POST">
                        @csrf

                        <div class="row mb-10-none">
                            <div class="col-xl-12 form-group">
                                <label>{{ __("Wallet Name") }}<span class="text-danger">*</span></label>
                                <input type="text" class="form--control" name="name" placeholder="{{ __('Enter wallet name') }}" value="{{ old('name') }}" required>
                            </div>

                            <div class="col-xl-6 form-group">
                                <label>{{ __("Blockchain Network") }}<span class="text-danger">*</span></label>
                                <select class="form--control select2" name="blockchain" required>
                                    <option value="" selected disabled>{{ __("Select Blockchain") }}</option>
                                    @foreach($supported_chains as $chain)
                                        <option value="{{ $chain['chain'] }}" {{ old('blockchain') == $chain['chain'] ? 'selected' : '' }}>
                                            {{ strtoupper($chain['coin']) }} - {{ ucfirst($chain['chain']) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-xl-6 form-group">
                                <label>{{ __("Currency") }}<span class="text-danger">*</span></label>
                                <select class="form--control select2" name="currency_code" required>
                                    <option value="" selected disabled>{{ __("Select Currency") }}</option>
                                    @foreach($supported_chains as $chain)
                                        <option value="{{ $chain['coin'] }}" {{ old('currency_code') == $chain['coin'] ? 'selected' : '' }}>
                                            {{ $chain['coin'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-xl-12 form-group">
                                <label>{{ __("Required Signatures") }}<span class="text-danger">*</span></label>
                                <input type="number" class="form--control" name="required_signatures" min="1" placeholder="{{ __('Number of signatures required') }}" value="{{ old('required_signatures', 2) }}" required>
                                <small class="text-muted">{{ __("Minimum number of approvals needed to execute a transaction") }}</small>
                            </div>

                            <div class="col-xl-12 form-group">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <label class="mb-0">{{ __("Wallet Signers") }}<span class="text-danger">*</span></label>
                                    <button type="button" class="btn btn-sm btn--base" id="add-signer">
                                        <i class="las la-plus"></i> {{ __("Add Signer") }}
                                    </button>
                                </div>
                                
                                <div id="signers-container">
                                    <!-- Signers will be added here dynamically -->
                                    <div class="signer-row" data-signer-index="0">
                                        <div class="row">
                                            <div class="col-md-5">
                                                <label>{{ __("Name") }}</label>
                                                <input type="text" class="form--control" name="signers[0][name]" placeholder="{{ __('Signer name') }}" required>
                                            </div>
                                            <div class="col-md-5">
                                                <label>{{ __("Email") }}</label>
                                                <input type="email" class="form--control" name="signers[0][email]" placeholder="{{ __('Signer email') }}" required>
                                            </div>
                                            <div class="col-md-2">
                                                <label>{{ __("Weight") }}</label>
                                                <input type="number" class="form--control" name="signers[0][weight]" min="1" value="1">
                                            </div>
                                        </div>
                                        <div class="row mt-2">
                                            <div class="col-md-6">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="signers[0][can_initiate]" value="1" checked>
                                                    <label class="form-check-label">{{ __("Can Initiate Transactions") }}</label>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="signers[0][can_approve]" value="1" checked>
                                                    <label class="form-check-label">{{ __("Can Approve Transactions") }}</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-xl-12 form-group">
                                <button type="submit" class="btn--base w-100">
                                    {{ __("Create Wallet") }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script')
<script>
    let signerIndex = 1;

    $('#add-signer').on('click', function() {
        const signerHtml = `
            <div class="signer-row" data-signer-index="${signerIndex}">
                <div class="d-flex justify-content-end mb-2">
                    <span class="remove-signer" onclick="removeSigner(${signerIndex})">
                        <i class="las la-times-circle"></i> ${__("Remove")}
                    </span>
                </div>
                <div class="row">
                    <div class="col-md-5">
                        <label>${__("Name")}</label>
                        <input type="text" class="form--control" name="signers[${signerIndex}][name]" placeholder="${__('Signer name')}" required>
                    </div>
                    <div class="col-md-5">
                        <label>${__("Email")}</label>
                        <input type="email" class="form--control" name="signers[${signerIndex}][email]" placeholder="${__('Signer email')}" required>
                    </div>
                    <div class="col-md-2">
                        <label>${__("Weight")}</label>
                        <input type="number" class="form--control" name="signers[${signerIndex}][weight]" min="1" value="1">
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-6">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="signers[${signerIndex}][can_initiate]" value="1" checked>
                            <label class="form-check-label">${__("Can Initiate Transactions")}</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="signers[${signerIndex}][can_approve]" value="1" checked>
                            <label class="form-check-label">${__("Can Approve Transactions")}</label>
                        </div>
                    </div>
                </div>
            </div>
        `;

        $('#signers-container').append(signerHtml);
        signerIndex++;
    });

    function removeSigner(index) {
        $(`[data-signer-index="${index}"]`).remove();
    }

    function __(key) {
        return key; // Placeholder for translation function
    }
</script>
@endpush
