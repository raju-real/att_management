@extends('layouts.app')
@section('title', 'Payment Gateway Settings')
@push('css')
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h3>Payment Gateway Settings</h3>
    </div>

    <div class="card admin-card">
        <div class="card-header">
            <h5 class="card-title"><i class="fas fa-credit-card mr-2"></i> Configure Payment Gateways</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('gateway-settings.update') }}" method="POST">
                @csrf
                @method('PUT')

                <div class="alert alert-info">
                    <i class="fas fa-info-circle mr-2"></i>
                    <strong>Note:</strong> Only one payment gateway can be active at a time. Select your preferred gateway
                    and configure its credentials below.
                </div>

                <!-- Active Gateway Selection -->
                <div class="form-group mb-4">
                    <label class="font-weight-bold">Select Active Gateway <span class="text-danger">*</span></label>
                    <div class="row mt-2">
                        <div class="col-md-3">
                            <div class="custom-control custom-radio">
                                <input type="radio" id="gateway_ssl" name="active_gateway" value="ssl_commerz"
                                    class="custom-control-input"
                                    {{ isset($settings->active_gateway) && $settings->active_gateway == 'ssl_commerz' ? 'checked' : '' }}>
                                <label class="custom-control-label" for="gateway_ssl">SSL COMMERZ</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="custom-control custom-radio">
                                <input type="radio" id="gateway_bkash" name="active_gateway" value="bkash"
                                    class="custom-control-input"
                                    {{ isset($settings->active_gateway) && $settings->active_gateway == 'bkash' ? 'checked' : '' }}>
                                <label class="custom-control-label" for="gateway_bkash">bKash</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="custom-control custom-radio">
                                <input type="radio" id="gateway_rocket" name="active_gateway" value="rocket"
                                    class="custom-control-input"
                                    {{ isset($settings->active_gateway) && $settings->active_gateway == 'rocket' ? 'checked' : '' }}>
                                <label class="custom-control-label" for="gateway_rocket">Rocket</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="custom-control custom-radio">
                                <input type="radio" id="gateway_nagad" name="active_gateway" value="nagad"
                                    class="custom-control-input"
                                    {{ isset($settings->active_gateway) && $settings->active_gateway == 'nagad' ? 'checked' : '' }}>
                                <label class="custom-control-label" for="gateway_nagad">Nagad</label>
                            </div>
                        </div>
                    </div>
                    @error('active_gateway')
                        {!! displayError($message) !!}
                    @enderror
                </div>

                <hr>

                <!-- SSL COMMERZ Settings -->
                <div class="accordion mb-3" id="gatewayAccordion">
                    <div class="card">
                        <div class="card-header" id="headingSsl">
                            <h5 class="mb-0">
                                <button class="btn btn-link" type="button" data-toggle="collapse"
                                    data-target="#collapseSsl" aria-expanded="true" aria-controls="collapseSsl">
                                    <i class="fas fa-lock mr-2"></i> SSL COMMERZ Configuration
                                </button>
                            </h5>
                        </div>
                        <div id="collapseSsl" class="collapse show" aria-labelledby="headingSsl"
                            data-parent="#gatewayAccordion">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Store ID</label>
                                            <input type="text" name="ssl_store_id" class="form-control"
                                                value="{{ $settings->gateways->ssl_commerz->store_id ?? '' }}"
                                                placeholder="your-store-id">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Store Password</label>
                                            <input type="password" name="ssl_store_password" class="form-control"
                                                value="{{ $settings->gateways->ssl_commerz->store_password ?? '' }}"
                                                placeholder="your-store-password">
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input" id="ssl_sandbox"
                                                name="ssl_sandbox_mode" value="1"
                                                {{ isset($settings->gateways->ssl_commerz->sandbox_mode) && $settings->gateways->ssl_commerz->sandbox_mode ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="ssl_sandbox">
                                                Enable Sandbox Mode (for testing)
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- bKash Settings -->
                    <div class="card">
                        <div class="card-header" id="headingBkash">
                            <h5 class="mb-0">
                                <button class="btn btn-link collapsed" type="button" data-toggle="collapse"
                                    data-target="#collapseBkash" aria-expanded="false" aria-controls="collapseBkash">
                                    <i class="fas fa-mobile-alt mr-2"></i> bKash Configuration
                                </button>
                            </h5>
                        </div>
                        <div id="collapseBkash" class="collapse" aria-labelledby="headingBkash"
                            data-parent="#gatewayAccordion">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>App Key</label>
                                            <input type="text" name="bkash_app_key" class="form-control"
                                                value="{{ $settings->gateways->bkash->app_key ?? '' }}"
                                                placeholder="your-app-key">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>App Secret</label>
                                            <input type="password" name="bkash_app_secret" class="form-control"
                                                value="{{ $settings->gateways->bkash->app_secret ?? '' }}"
                                                placeholder="your-app-secret">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Username</label>
                                            <input type="text" name="bkash_username" class="form-control"
                                                value="{{ $settings->gateways->bkash->username ?? '' }}"
                                                placeholder="your-username">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Password</label>
                                            <input type="password" name="bkash_password" class="form-control"
                                                value="{{ $settings->gateways->bkash->password ?? '' }}"
                                                placeholder="your-password">
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input" id="bkash_sandbox"
                                                name="bkash_sandbox_mode" value="1"
                                                {{ isset($settings->gateways->bkash->sandbox_mode) && $settings->gateways->bkash->sandbox_mode ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="bkash_sandbox">
                                                Enable Sandbox Mode (for testing)
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Rocket Settings -->
                    <div class="card">
                        <div class="card-header" id="headingRocket">
                            <h5 class="mb-0">
                                <button class="btn btn-link collapsed" type="button" data-toggle="collapse"
                                    data-target="#collapseRocket" aria-expanded="false" aria-controls="collapseRocket">
                                    <i class="fas fa-rocket mr-2"></i> Rocket Configuration
                                </button>
                            </h5>
                        </div>
                        <div id="collapseRocket" class="collapse" aria-labelledby="headingRocket"
                            data-parent="#gatewayAccordion">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Merchant ID</label>
                                            <input type="text" name="rocket_merchant_id" class="form-control"
                                                value="{{ $settings->gateways->rocket->merchant_id ?? '' }}"
                                                placeholder="your-merchant-id">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Merchant Password</label>
                                            <input type="password" name="rocket_merchant_password" class="form-control"
                                                value="{{ $settings->gateways->rocket->merchant_password ?? '' }}"
                                                placeholder="your-merchant-password">
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input" id="rocket_sandbox"
                                                name="rocket_sandbox_mode" value="1"
                                                {{ isset($settings->gateways->rocket->sandbox_mode) && $settings->gateways->rocket->sandbox_mode ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="rocket_sandbox">
                                                Enable Sandbox Mode (for testing)
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Nagad Settings -->
                    <div class="card">
                        <div class="card-header" id="headingNagad">
                            <h5 class="mb-0">
                                <button class="btn btn-link collapsed" type="button" data-toggle="collapse"
                                    data-target="#collapseNagad" aria-expanded="false" aria-controls="collapseNagad">
                                    <i class="fas fa-wallet mr-2"></i> Nagad Configuration
                                </button>
                            </h5>
                        </div>
                        <div id="collapseNagad" class="collapse" aria-labelledby="headingNagad"
                            data-parent="#gatewayAccordion">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Merchant ID</label>
                                            <input type="text" name="nagad_merchant_id" class="form-control"
                                                value="{{ $settings->gateways->nagad->merchant_id ?? '' }}"
                                                placeholder="your-merchant-id">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Merchant Key</label>
                                            <input type="password" name="nagad_merchant_key" class="form-control"
                                                value="{{ $settings->gateways->nagad->merchant_key ?? '' }}"
                                                placeholder="your-merchant-key">
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input" id="nagad_sandbox"
                                                name="nagad_sandbox_mode" value="1"
                                                {{ isset($settings->gateways->nagad->sandbox_mode) && $settings->gateways->nagad->sandbox_mode ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="nagad_sandbox">
                                                Enable Sandbox Mode (for testing)
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i> Save Gateway Settings
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('js')
@endpush
