@extends('layouts.app')
@section('title', 'Fee Lot Details')
@push('css')
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h3>{{ $feeLot->title }}</h3>
        <a href="{{ route('fee-lots.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-1"></i> Back to List
        </a>
    </div>

    @php
        $filterOpen = request()->hasAny(['student_id', 'class', 'status']);
    @endphp

    <div class="accordion mb-3" id="feeFilterAccordion">
        <div class="card">
            <div class="card-header p-0" id="filterHeading">
                <button class="btn btn-link btn-block text-left d-flex justify-content-between align-items-center"
                    type="button" data-toggle="collapse" data-target="#filterCollapse"
                    aria-expanded="{{ $filterOpen ? 'true' : 'false' }}" aria-controls="filterCollapse">
                    <span>
                        <i class="fas fa-filter mr-1"></i> Filter Student Fees
                    </span>
                    <i class="fas fa-chevron-down"></i>
                </button>
            </div>

            <div id="filterCollapse" class="collapse {{ $filterOpen ? 'show' : '' }}" aria-labelledby="filterHeading"
                data-parent="#feeFilterAccordion">

                <div class="card-body">
                    <form method="GET" action="{{ route('fee-lots.show', $feeLot->id) }}">
                        <div class="row g-2">

                            <!-- Student ID -->
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="form-label">Student ID</label>
                                    <input type="search" name="student_id" class="form-control"
                                        value="{{ request('student_id') }}" placeholder="Student ID">
                                </div>
                            </div>

                            <!-- Class -->
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="form-label">Class</label>
                                    <select name="class" class="form-control">
                                        <option value="">All Classes</option>
                                        @foreach (getClassList() as $class)
                                            <option value="{{ $class }}"
                                                {{ request('class') == $class ? 'selected' : '' }}>
                                                {{ $class }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <!-- Status -->
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="form-label">Payment Status</label>
                                    <select name="status" class="form-control">
                                        <option value="">All Status</option>
                                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>
                                            Pending
                                        </option>
                                        <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>
                                            Paid
                                        </option>
                                        <option value="partial" {{ request('status') == 'partial' ? 'selected' : '' }}>
                                            Partial
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <!-- Buttons -->
                            <div class="col-md-1 mt-2">
                                <div class="form-group">
                                    <label class="form-label"></label>
                                    <button class="btn btn-primary mr-2 w-100">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-1 mt-2">
                                <label class="form-label"></label>
                                <div class="form-group">
                                    <a href="{{ route('fee-lots.show', $feeLot->id) }}" class="btn btn-secondary w-100">
                                        <i class="fas fa-undo"></i>
                                    </a>
                                </div>
                            </div>

                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="card admin-card">
        <div class="card-header">
            <h5 class="card-title">
                <i class="fas fa-money-bill-wave mr-2"></i>
                Student Fees ({{ dateFormat($feeLot->start_date) }} to {{ dateFormat($feeLot->end_date) }})
            </h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Student ID</th>
                            <th>Student Name</th>
                            <th>Class</th>
                            <th>Amount</th>
                            <th>Paid Amount</th>
                            <th>Status</th>
                            <th>Payment Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($studentFees as $fee)
                            <tr>
                                <td>{{ ($studentFees->currentPage() - 1) * $studentFees->perPage() + $loop->iteration }}
                                </td>
                                <td>{{ $fee->student->student_id ?? 'N/A' }}</td>
                                <td>{{ showStudentFullName($fee->student->firstname ?? '', $fee->student->middlname ?? '', $fee->student->lastname ?? '') }}
                                </td>
                                <td>{{ $fee->student->class ?? 'N/A' }}</td>
                                <td>{{ numberFormat($fee->amount) }} BDT</td>
                                <td>{{ numberFormat($fee->paid_amount ?? 0) }} BDT</td>
                                <td>
                                    @if ($fee->status == 'paid')
                                        <span class="badge badge-success">Paid</span>
                                    @elseif($fee->status == 'partial')
                                        <span class="badge badge-warning">Partial</span>
                                    @else
                                        <span class="badge badge-danger">Pending</span>
                                    @endif
                                </td>
                                <td>{{ $fee->payment_date ? dateFormat($fee->payment_date) : '-' }}</td>
                                <td>
                                    @if ($fee->status != 'paid')
                                        <a href="{{ route('payment.initiate', $fee->id) }}" class="btn btn-sm btn-success"
                                            {!! tooltip('Pay Now') !!}>
                                            <i class="fas fa-credit-card"></i> Pay Now
                                        </a>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <x-no-data-found />
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-center">
                {!! $studentFees->appends(request()->query())->links('pagination::bootstrap-4') !!}
            </div>
        </div>
    </div>
@endsection

@push('js')
@endpush
