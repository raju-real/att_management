@extends('layouts.app')
@section('title', 'Institute User Add/Edit')

@php $granted = (array) (old('permissions') ?? ($user->permissions ?? [])); @endphp

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h3>{{ isset($user) ? 'Edit' : 'Add' }} Institute User</h3>
        <a href="{{ route('institute-users.index') }}" class="btn btn-secondary" {!! tooltip('Back to List') !!}>
            <i class="fas fa-arrow-left mr-2"></i> Back
        </a>
    </div>

    <form action="{{ $route }}" id="prevent-form" method="POST">
        @csrf
        @isset($user)
            @method('PUT')
        @endisset

        <div class="card admin-card">
            <div class="card-header">
                <h5 class="card-title"><i class="fas fa-user mr-2"></i> User Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Institute {!! starSign() !!}</label>
                            @include('partials.institute_select', ['required' => true, 'selected' => $user->institute_id ?? null])
                            @error('institute_id') {!! displayError($message) !!} @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Name {!! starSign() !!}</label>
                            <input type="text" name="name" value="{{ old('name', $user->name ?? '') }}" class="form-control {{ hasError('name') }}" placeholder="Enter name">
                            @error('name') {!! displayError($message) !!} @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Mobile</label>
                            <input type="text" name="mobile" value="{{ old('mobile', $user->mobile ?? '') }}" class="form-control {{ hasError('mobile') }}" placeholder="017XXXXXXXX">
                            @error('mobile') {!! displayError($message) !!} @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Login Email {!! starSign() !!}</label>
                            <input type="email" name="email" value="{{ old('email', $user->email ?? '') }}" class="form-control {{ hasError('email') }}" autocomplete="off" placeholder="Enter login Email">
                            @error('email') {!! displayError($message) !!} @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Password @unless(isset($user)) {!! starSign() !!} @endunless</label>
                            <input type="password" name="password" class="form-control {{ hasError('password') }}" autocomplete="new-password" placeholder="Password">
                            @isset($user)<small class="text-muted">Leave blank to keep the current password.</small>@endisset
                            @error('password') {!! displayError($message) !!} @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Confirm Password</label>
                            <input type="password" name="password_confirmation" class="form-control" autocomplete="new-password" placeholder="Confirm Password">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Status {!! starSign() !!}</label>
                            <select name="status" class="form-control {{ hasError('status') }}">
                                @foreach (getStatus() as $status)
                                    <option value="{{ $status->value }}" {{ old('status', $user->status ?? 'active') === $status->value ? 'selected' : '' }}>{{ $status->title }}</option>
                                @endforeach
                            </select>
                            @error('status') {!! displayError($message) !!} @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ═══ Menu / submenu permissions ═══ --}}
        <div class="card admin-card mt-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0"><i class="fas fa-key mr-2"></i> Menu Permissions {!! starSign() !!}
                    <small class="text-muted">(the user sees and opens only the ticked menus)</small></h5>
                <div>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-perm-all="1">Select all</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-perm-all="0">Clear</button>
                </div>
            </div>
            <div class="card-body">
                @error('permissions') <div class="alert alert-danger py-2">{{ $message }}</div> @enderror
                <div class="row">
                    @foreach (\App\Support\Permissions::MENU as $menu => $items)
                        <div class="col-md-4 mb-3">
                            <div class="border rounded p-3 h-100 perm-group">
                                <label class="font-weight-bold mb-2 d-block" style="cursor:pointer">
                                    <input type="checkbox" class="perm-menu mr-1"> {{ $menu }}
                                </label>
                                @foreach ($items as $key => $item)
                                    <label class="d-block mb-1 ml-3" style="cursor:pointer">
                                        <input type="checkbox" name="permissions[]" value="{{ $key }}" class="perm-item mr-1"
                                               {{ in_array($key, $granted, true) ? 'checked' : '' }}>
                                        <i class="fas {{ $item['icon'] }} text-muted mr-1" style="width:16px"></i>{{ $item['label'] }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="text-right mt-3">
            <x-submit-button />
        </div>
    </form>
@endsection

@push('js')
<script>
// Menu checkbox = select / clear all its submenus; it shows checked when all are.
(function () {
    function syncMenu(group) {
        const items = group.querySelectorAll('.perm-item');
        const on = Array.from(items).filter(i => i.checked).length;
        const menu = group.querySelector('.perm-menu');
        menu.checked = on === items.length;
        menu.indeterminate = on > 0 && on < items.length;
    }
    document.querySelectorAll('.perm-group').forEach(function (group) {
        group.querySelector('.perm-menu').addEventListener('change', function () {
            group.querySelectorAll('.perm-item').forEach(i => i.checked = this.checked);
        });
        group.querySelectorAll('.perm-item').forEach(i => i.addEventListener('change', () => syncMenu(group)));
        syncMenu(group);
    });
    document.querySelectorAll('[data-perm-all]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const on = btn.dataset.permAll === '1';
            document.querySelectorAll('.perm-item').forEach(i => i.checked = on);
            document.querySelectorAll('.perm-group').forEach(syncMenu);
        });
    });
})();
</script>
@endpush
