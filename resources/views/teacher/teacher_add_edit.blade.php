@extends('layouts.app')
@section('title', 'Teacher Add/Edit')

@push('css')
<style>
    /* ── Image Upload Zone ── */
    .teacher-img-zone {
        border: 2px dashed rgba(99, 102, 241, 0.5);
        border-radius: 16px;
        background: rgba(99, 102, 241, 0.04);
        padding: 24px 16px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
    }
    .teacher-img-zone:hover {
        border-color: #6366f1;
        background: rgba(99, 102, 241, 0.09);
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
    }
    .teacher-img-zone input[type="file"] {
        position: absolute;
        inset: 0;
        opacity: 0;
        cursor: pointer;
        width: 100%;
        height: 100%;
    }
    .img-preview-wrap {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        overflow: hidden;
        margin: 0 auto 12px;
        border: 3px solid rgba(99, 102, 241, 0.4);
        background: #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 15px rgba(99,102,241,0.2);
        transition: border-color 0.3s;
    }
    .img-preview-wrap img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .img-preview-wrap .no-img-icon {
        font-size: 48px;
        color: #c4c9d4;
    }
    .img-upload-label {
        font-size: 13px;
        color: #6b7280;
        margin-bottom: 4px;
    }
    .img-upload-label span {
        color: #6366f1;
        font-weight: 600;
    }
    .remove-img-btn {
        display: none;
        margin-top: 10px;
    }
    .img-preview-wrap.has-image {
        border-color: #6366f1;
    }
</style>
@endpush

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h3>{{ isset($teacher) ? 'Edit' : 'Add' }} Teacher</h3>
        <a href="{{ route('teachers.index') }}" class="btn btn-secondary" {!! tooltip('Back to List') !!}>
            <i class="fas fa-arrow-left mr-2"></i> Back
        </a>
    </div>

    <div class="card admin-card">
        <div class="card-header">
            <h5 class="card-title"><i class="fas fa-plus-circle mr-2"></i> Teacher Information</h5>
        </div>
        <div class="card-body">
            <form action="{{ $route }}" id="prevent-form" method="POST" enctype="multipart/form-data">
                @csrf
                @isset($teacher)
                    @method('PUT')
                @endisset

                <div class="row">
                    {{-- ── Image Upload Column ── --}}
                    <div class="col-md-3 mb-3">
                        <label class="form-label d-block">Teacher Photo</label>
                        <div class="teacher-img-zone" id="imgZone">
                            <input type="file" name="image" id="teacherImageInput"
                                   accept="image/jpeg,image/png,image/jpg,image/gif,image/webp">
                            <div class="img-preview-wrap" id="imgPreviewWrap">
                                @if(isset($teacher) && $teacher->image && file_exists($teacher->image))
                                    <img id="imgPreview" src="{{ asset($teacher->image) }}" alt="Teacher Photo">
                                @else
                                    <img id="imgPreview" src="" alt="Preview" style="display:none;">
                                    <i class="fas fa-user-circle no-img-icon" id="noImgIcon"></i>
                                @endif
                            </div>
                            <p class="img-upload-label mb-0">
                                <span><i class="fas fa-cloud-upload-alt"></i> Click to upload</span><br>
                                <small>JPG, PNG, GIF or WEBP &bull; Max 2MB</small>
                            </p>
                        </div>
                        @error('image')
                            {!! displayError($message) !!}
                        @enderror

                        {{-- Remove image option on edit --}}
                        @isset($teacher)
                            @if($teacher->image && file_exists($teacher->image))
                                <div class="mt-2 remove-img-btn" id="removeImgWrap" style="display:block!important;">
                                    <label class="d-flex align-items-center" style="cursor:pointer;font-size:13px;color:#ef4444;">
                                        <input type="checkbox" name="remove_image" value="1" id="removeImgCheck" class="mr-1">
                                        Remove current photo
                                    </label>
                                </div>
                            @endif
                        @endisset
                    </div>

                    {{-- ── Form Fields ── --}}
                    <div class="col-md-9">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Name {!! starSign() !!}</label>
                                    <input type="text" name="name" value="{{ old('name') ?? ($teacher->name ?? '') }}"
                                        class="form-control {{ hasError('name') }}" placeholder="Full Name">
                                    @error('name')
                                        {!! displayError($message) !!}
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Email {!! starSign() !!}</label>
                                    <input type="text" name="email" value="{{ old('email') ?? ($teacher->email ?? '') }}"
                                        class="form-control {{ hasError('email') }}" placeholder="Email Address">
                                    @error('email')
                                        {!! displayError($message) !!}
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Mobile {!! starSign() !!}</label>
                                    <input type="text" name="mobile" value="{{ old('mobile') ?? ($teacher->mobile ?? '') }}"
                                        class="form-control {{ hasError('mobile') }}" placeholder="Mobile Number">
                                    @error('mobile')
                                        {!! displayError($message) !!}
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Designation {!! starSign() !!}</label>
                                    <input type="text" name="designation"
                                        value="{{ old('designation') ?? ($teacher->designation ?? '') }}"
                                        class="form-control {{ hasError('designation') }}" placeholder="Designation">
                                    @error('designation')
                                        {!! displayError($message) !!}
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="text-right mt-2">
                    <x-submit-button />
                </div>
            </form>
        </div>
    </div>
@endsection

@push('js')
<script>
(function () {
    const input   = document.getElementById('teacherImageInput');
    const preview = document.getElementById('imgPreview');
    const noIcon  = document.getElementById('noImgIcon');
    const wrap    = document.getElementById('imgPreviewWrap');

    if (!input) return;

    // If we already have an image (edit mode) — mark the wrap
    if (preview && preview.src && preview.src !== window.location.href) {
        wrap.classList.add('has-image');
    }

    input.addEventListener('change', function () {
        const file = this.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = function (e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
            if (noIcon) noIcon.style.display = 'none';
            wrap.classList.add('has-image');
        };
        reader.readAsDataURL(file);
    });

    // Remove image checkbox — hide preview
    const removeCheck = document.getElementById('removeImgCheck');
    if (removeCheck) {
        removeCheck.addEventListener('change', function () {
            if (this.checked) {
                preview.src = '';
                preview.style.display = 'none';
                if (noIcon) noIcon.style.display = '';
                wrap.classList.remove('has-image');
            } else {
                // Restore original image
                preview.src = '{{ isset($teacher) && $teacher->image ? asset($teacher->image) : "" }}';
                preview.style.display = preview.src ? 'block' : 'none';
                if (noIcon) noIcon.style.display = preview.src ? 'none' : '';
                if (preview.src) wrap.classList.add('has-image');
            }
        });
    }
})();
</script>
@endpush
