@extends('layouts.app')
@section('title','Update Profile')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h3>Update Profile</h3>
    </div>

    <form action="{{ route('update-profile') }}" id="prevent-form" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="card admin-card mb-3">
            <div class="card-header">
                <h5 class="card-title"><i class="fas fa-user mr-2"></i> Basic Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-2 text-center mb-3">
                        @if (authUser()->image && file_exists(authUser()->image))
                            <img src="{{ asset(authUser()->image) }}" alt="" id="profilePreview"
                                 class="rounded-circle border" style="width:96px;height:96px;object-fit:cover">
                        @else
                            <img src="" alt="" id="profilePreview" class="rounded-circle border d-none" style="width:96px;height:96px;object-fit:cover">
                            <i class="fas fa-user-circle text-muted" id="profileIcon" style="font-size:96px"></i>
                        @endif
                    </div>
                    <div class="col-md-10">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">Name {!! starSign() !!}</label>
                                    <input type="text" name="name" maxlength="50" value="{{ old('name') ?? authUser()->name ?? '' }}"
                                           class="form-control {{ hasError('name') }}" placeholder="Full name">
                                    @error('name')
                                    {!! displayError($message) !!}
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">Email {!! starSign() !!}
                                        <i class="fas fa-question-circle text-muted" {!! tooltip('Used to sign in') !!}></i></label>
                                    <input type="email" name="email" maxlength="30" value="{{ old('email') ?? authUser()->email ?? '' }}"
                                           class="form-control {{ hasError('email') }}" placeholder="admin@school.edu">
                                    @error('email')
                                    {!! displayError($message) !!}
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">Mobile {!! starSign() !!}</label>
                                    <input type="text" name="mobile" maxlength="11" inputmode="numeric"
                                           value="{{ old('mobile') ?? authUser()->mobile ?? '' }}"
                                           class="form-control {{ hasError('mobile') }}" placeholder="01XXXXXXXXX">
                                    @error('mobile')
                                    {!! displayError($message) !!}
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label">Photo <small class="text-muted">(JPG/PNG/WEBP, max 1 MB)</small></label>
                                    <input type="file" name="image" id="profileImage"
                                           class="form-control-file {{ hasError('image') }}" accept=".jpg,.jpeg,.png,.webp">
                                    @error('image')
                                    {!! displayError($message) !!}
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card admin-card">
            <div class="card-header">
                <h5 class="card-title"><i class="fas fa-key mr-2"></i> Change Password
                    <small class="text-muted ml-1">(leave empty to keep your current password)</small></h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Current Password</label>
                            <input type="password" name="current_password" autocomplete="current-password"
                                   class="form-control {{ hasError('current_password') }}" placeholder="Current password">
                            @error('current_password')
                            {!! displayError($message) !!}
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">New Password</label>
                            <input type="password" name="password" autocomplete="new-password"
                                   class="form-control {{ hasError('password') }}" placeholder="New password">
                            <small class="text-muted">At least 6 characters, with letters and numbers.</small>
                            @error('password')
                            {!! displayError($message) !!}
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" name="password_confirmation" autocomplete="new-password"
                                   class="form-control" placeholder="Repeat new password">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-right mt-3">
            <x-submit-button/>
        </div>
    </form>
@endsection

@push('js')
<script>
    document.getElementById('profileImage')?.addEventListener('change', function () {
        const file = this.files && this.files[0];
        if (!file) return;
        const img = document.getElementById('profilePreview');
        img.src = URL.createObjectURL(file);
        img.classList.remove('d-none');
        document.getElementById('profileIcon')?.classList.add('d-none');
    });
</script>
@endpush
