@extends('layouts.app')
@section('title', 'Transaction Details')
@push('css')
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h3>Transaction Details</h3>
        <a href="{{ route('fee-lots.show', encrypt_decrypt($transaction->studentFee->fee_lot_id, 'encrypt')) }}"
            class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-1"></i> Back
        </a>
    </div>

    <div class="row">
        <!-- Transaction Information -->
        <div class="col-md-8">
            <div class="card admin-card mb-3">
                <div class="card-header">
                    <h5 class="card-title"><i class="fas fa-file-invoice mr-2"></i> Transaction Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Transaction ID:</strong> {{ $transaction->transaction_id }}</p>
                            <p><strong>Gateway:</strong> {{ strtoupper($transaction->gateway) }}</p>
                            <p><strong>Amount:</strong> {{ numberFormat($transaction->transaction_amount) }}
                                {{ $transaction->currency }}</p>
                            <p><strong>Status:</strong>
                                @if ($transaction->status == 'SUCCESS')
                                    <span class="badge badge-success">SUCCESS</span>
                                @elseif($transaction->status == 'PENDING')
                                    <span class="badge badge-warning">PENDING</span>
                                @elseif($transaction->status == 'REFUNDED')
                                    <span class="badge badge-info">REFUNDED</span>
                                @else
                                    <span class="badge badge-danger">{{ $transaction->status }}</span>
                                @endif
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Bank Tran ID:</strong> {{ $transaction->bank_tran_id ?? 'N/A' }}</p>
                            <p><strong>Card Type:</strong> {{ $transaction->card_type ?? 'N/A' }}</p>
                            <p><strong>Card Number:</strong> {{ $transaction->card_no ?? 'N/A' }}</p>
                            <p><strong>Transaction Date:</strong>
                                {{ $transaction->transaction_date ? dateFormat($transaction->transaction_date) : 'N/A' }}
                            </p>
                        </div>
                    </div>

                    @if ($transaction->card_issuer)
                        <hr>
                        <h6>Card Details</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Card Issuer:</strong> {{ $transaction->card_issuer }}</p>
                                <p><strong>Card Brand:</strong> {{ $transaction->card_brand ?? 'N/A' }}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Card Sub-Brand:</strong> {{ $transaction->card_sub_brand ?? 'N/A' }}</p>
                                <p><strong>Issuer Country:</strong> {{ $transaction->card_issuer_country ?? 'N/A' }}</p>
                            </div>
                        </div>
                    @endif

                    @if ($transaction->status == 'REFUNDED')
                        <hr>
                        <div class="alert alert-info">
                            <h6><i class="fas fa-undo mr-2"></i> Refund Information</h6>
                            <p><strong>Refund Amount:</strong> {{ numberFormat($transaction->refund_amount) }}
                                {{ $transaction->currency }}</p>
                            <p><strong>Refund Ref ID:</strong> {{ $transaction->refund_ref_id }}</p>
                            <p><strong>Refund Date:</strong> {{ dateFormat($transaction->refund_date) }}</p>
                            <p><strong>Remarks:</strong> {{ $transaction->refund_remarks ?? 'N/A' }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Student & Fee Information -->
        <div class="col-md-4">
            <div class="card admin-card mb-3">
                <div class="card-header">
                    <h5 class="card-title"><i class="fas fa-user mr-2"></i> Student Information</h5>
                </div>
                <div class="card-body">
                    <p><strong>Name:</strong>
                        {{ showStudentFullName($transaction->studentFee->student->firstname ?? '', $transaction->studentFee->student->middlname ?? '', $transaction->studentFee->student->lastname ?? '') }}
                    </p>
                    <p><strong>Student ID:</strong> {{ $transaction->studentFee->student->student_id ?? 'N/A' }}</p>
                    <p><strong>Class:</strong> {{ $transaction->studentFee->student->class ?? 'N/A' }}</p>
                    <p><strong>Phone:</strong> {{ $transaction->studentFee->student->phone ?? 'N/A' }}</p>
                </div>
            </div>

            <div class="card admin-card">
                <div class="card-header">
                    <h5 class="card-title"><i class="fas fa-money-bill mr-2"></i> Fee Information</h5>
                </div>
                <div class="card-body">
                    <p><strong>Fee Lot:</strong> {{ $transaction->studentFee->feeLot->title }}</p>
                    <p><strong>Total Amount:</strong> {{ numberFormat($transaction->studentFee->amount) }} BDT</p>
                    <p><strong>Paid Amount:</strong> {{ numberFormat($transaction->studentFee->paid_amount ?? 0) }} BDT</p>
                    <p><strong>Due Amount:</strong>
                        {{ numberFormat($transaction->studentFee->amount - ($transaction->studentFee->paid_amount ?? 0)) }}
                        BDT</p>
                    <p><strong>Payment Status:</strong>
                        @if ($transaction->studentFee->status == 'paid')
                            <span class="badge badge-success">Paid</span>
                        @elseif($transaction->studentFee->status == 'partial')
                            <span class="badge badge-warning">Partial</span>
                        @else
                            <span class="badge badge-danger">Pending</span>
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Refund Section -->
    @if ($transaction->status == 'SUCCESS' && authUser()->role == 'admin')
        <div class="card admin-card">
            <div class="card-header bg-danger text-white">
                <h5 class="card-title mb-0"><i class="fas fa-undo mr-2"></i> Refund Transaction</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <strong>Warning:</strong> Refunding this transaction will reverse the payment and update the student's
                    fee status accordingly. This action cannot be undone easily.
                </div>

                <form action="{{ route('payment.transaction.refund', $transaction->transaction_id) }}" method="POST"
                    id="prevent-form">
                    @csrf
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Refund Amount <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" name="refund_amount"
                                    class="form-control {{ hasError('refund_amount') }}"
                                    max="{{ $transaction->transaction_amount }}"
                                    value="{{ old('refund_amount', $transaction->transaction_amount) }}"
                                    placeholder="Enter refund amount">
                                <small class="text-muted">Max: {{ numberFormat($transaction->transaction_amount) }}
                                    BDT</small>
                                @error('refund_amount')
                                    {!! displayError($message) !!}
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Refund Remarks</label>
                                <textarea name="refund_remarks" class="form-control {{ hasError('refund_remarks') }}" rows="2"
                                    placeholder="Optional remarks for refund">{{ old('refund_remarks') }}</textarea>
                                @error('refund_remarks')
                                    {!! displayError($message) !!}
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="text-right">
                        <button type="submit" class="btn btn-danger"
                            onclick="return confirm('Are you sure you want to process this refund? This action cannot be undone.')">
                            <i class="fas fa-undo mr-1"></i> Process Refund
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection

@push('js')
@endpush
