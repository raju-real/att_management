@extends('layouts.app')
@section('title', 'Import Students')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h3>Import Students</h3>
        <a href="{{ route('students.index') }}" class="btn btn-secondary" {!! tooltip('Back to List') !!}>
            <i class="fas fa-arrow-left mr-2"></i> Back
        </a>
    </div>

    <div class="card admin-card">
        <div class="card-header">
            <h5 class="card-title"><i class="fas fa-file-excel mr-2"></i> Upload Students File</h5>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <a href="{{ route('students.import.demo') }}" class="btn btn-info btn-sm float-right text-white"><i
                        class="fas fa-download mr-1"></i> Demo CSV</a>
                <strong>Important:</strong> Your Excel or CSV file must have exactly these header columns:
                <br><code>student_id</code>, <code>firstname</code>, <code>middlename</code>, <code>lastname</code>,
                <code>nickname</code>, <code>class</code>, <code>section</code>, <code>roll</code>, <code>shift</code>,
                <code>medium</code>, <code>group</code>.
                <br><code>student_id</code> and <code>firstname</code> are required.
            </div>

            <form action="{{ route('students.upload') }}" method="POST" enctype="multipart/form-data" id="prevent-form">
                @csrf
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label">Select File (XLS, XLSX, CSV) {!! starSign() !!}</label>
                            <input type="file" name="file"
                                class="form-control-file @error('file') is-invalid @enderror"
                                accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel">
                            @error('file')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
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
