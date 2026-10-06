@extends('layouts.app')
@section('title', 'Fee Collect Lots')
@push('css')
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h3>Fee Collect Lots</h3>
        <a href="{{ route('fee-lots.create') }}" class="btn btn-primary btn-sm text-white">
            <i class="fas fa-plus-circle"></i> Add New
        </a>
    </div>

    <div class="card admin-card">
        <div class="card-header">
            <h5 class="card-title"><i class="fas fa-layer-group mr-2"></i> Fee Lot History</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Title</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($feeLots as $lot)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $lot->title }}</td>
                                <td>{{ dateFormat($lot->start_date) }}</td>
                                <td>{{ dateFormat($lot->end_date) }}</td>
                                <td>
                                    @if ($lot->is_active)
                                        <span class="badge badge-success">Active</span>
                                    @else
                                        <span class="badge badge-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-nowrap">
                                    <a href="{{ route('fee-lots.show', encrypt_decrypt($lot->id, 'encrypt')) }}"
                                        class="action-btn text-info" {!! tooltip('Edit Fee Collect Lot') !!}><i class="fas fa-eye"></i></a>
                                    <a href="{{ route('fee-lots.edit', encrypt_decrypt($lot->id, 'encrypt')) }}"
                                        class="action-btn text-primary" {!! tooltip('Edit Fee Collect Lot') !!}><i class="fas fa-edit"></i></a>
                                    <a {!! tooltip('Delete Fee Lot') !!} class="action-btn text-danger delete-data"
                                        data-id="{{ 'delete-lot-' . $lot->id }}" href="javascript:void(0);">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                    <form id="delete-lot-{{ $lot->id }}"
                                        action="{{ route('fee-lots.destroy', $lot->id) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                    </form>
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
                {!! $feeLots->links('pagination::bootstrap-4') !!}
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script>
        $(document).on('click', '.delete-item', function() {
            var id = $(this).data('id');
            confirmDelete({
                url: '{{ url('fee-lots') }}/' + id,
                title: 'Delete Fee Lot?',
                text: 'This will also delete all associated student fees (if no payments made).',
                confirmButtonText: 'Yes, Delete',
            });
        });
    </script>
@endpush
