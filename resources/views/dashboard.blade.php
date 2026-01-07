@extends('layouts.app')
@section('title', 'Dashboard')
@push('css')
@endpush

@section('content')
    <h2 class="mb-2">Dashboard Overview</h2>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card admin-card stats-card primary">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Sales
                            </div>
                            <div class="stats-number">$24,000</div>
                            <div class="text-muted">Last 30 days</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign stats-icon text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card admin-card stats-card success">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Total Orders
                            </div>
                            <div class="stats-number">1,250</div>
                            <div class="text-muted">Completed orders</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-shopping-cart stats-icon text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card admin-card stats-card warning">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                New Customers
                            </div>
                            <div class="stats-number">324</div>
                            <div class="text-muted">This month</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-plus stats-icon text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card admin-card stats-card danger">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                Pending Orders
                            </div>
                            <div class="stats-number">18</div>
                            <div class="text-muted">Awaiting processing</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock stats-icon text-danger"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Orders -->
    <div class="row">
        <div class="col-12">
            <div class="card admin-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title"><i class="fas fa-shopping-cart"></i> Today Attendance</h5>
                    <a href="products.html" class="btn btn-primary-admin btn-sm">
                        View All Products
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td>#ORD-001</td>
                                <td>John Smith</td>
                                <td>2023-10-15</td>
                                <td>$245.99</td>
                                <td><span class="badge-status badge-active">Completed</span></td>
                                <td>
                                    <button class="action-btn" title="View"><i class="fas fa-eye"></i></button>
                                </td>
                            </tr>
                            <tr>
                                <td>#ORD-002</td>
                                <td>Emma Johnson</td>
                                <td>2023-10-14</td>
                                <td>$120.50</td>
                                <td><span class="badge-status badge-active">Shipped</span></td>
                                <td>
                                    <button class="action-btn" title="View"><i class="fas fa-eye"></i></button>
                                </td>
                            </tr>
                            <tr>
                                <td>#ORD-003</td>
                                <td>Michael Brown</td>
                                <td>2023-10-13</td>
                                <td>$89.99</td>
                                <td><span class="badge-status badge-inactive">Pending</span></td>
                                <td>
                                    <button class="action-btn" title="View"><i class="fas fa-eye"></i></button>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
@endpush
