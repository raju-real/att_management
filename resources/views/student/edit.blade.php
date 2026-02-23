@extends('layouts.app')
@section('title', 'Edit Student')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h3>Edit Student</h3>
        <a href="{{ route('students.index') }}" class="btn btn-secondary" {!! tooltip('Back to List') !!}>
            <i class="fas fa-arrow-left mr-2"></i> Back
        </a>
    </div>

    <div class="card admin-card">
        <div class="card-header">
            <h5 class="card-title"><i class="fas fa-edit mr-2"></i> Edit Student Information</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('students.update', $student->id) }}" method="POST" id="prevent-form">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Student ID {!! starSign() !!}</label>
                            <input type="text" name="student_id" value="{{ old('student_id') ?? $student->student_id }}"
                                class="form-control {{ hasError('student_id') }}" placeholder="Student ID">
                            @error('student_id')
                                {!! displayError($message) !!}
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">First Name {!! starSign() !!}</label>
                            <input type="text" name="firstname" value="{{ old('firstname') ?? $student->firstname }}"
                                class="form-control {{ hasError('firstname') }}" placeholder="First Name">
                            @error('firstname')
                                {!! displayError($message) !!}
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Middle Name</label>
                            <input type="text" name="middlename" value="{{ old('middlename') ?? $student->middlename }}"
                                class="form-control {{ hasError('middlename') }}" placeholder="Middle Name">
                            @error('middlename')
                                {!! displayError($message) !!}
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Last Name</label>
                            <input type="text" name="lastname" value="{{ old('lastname') ?? $student->lastname }}"
                                class="form-control {{ hasError('lastname') }}" placeholder="Last Name">
                            @error('lastname')
                                {!! displayError($message) !!}
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Nick Name</label>
                            <input type="text" name="nickname" value="{{ old('nickname') ?? $student->nickname }}"
                                class="form-control {{ hasError('nickname') }}" placeholder="Nick Name">
                            @error('nickname')
                                {!! displayError($message) !!}
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Class</label>
                            <select name="class" class="form-control {{ hasError('class') }}">
                                <option value="">Select Class</option>
                                @foreach (getClassList() as $className)
                                    <option value="{{ $className }}"
                                        {{ (old('class') ?? $student->class) == $className ? 'selected' : '' }}>
                                        {{ $className }}</option>
                                @endforeach
                            </select>
                            @error('class')
                                {!! displayError($message) !!}
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Section</label>
                            <input type="text" name="section" value="{{ old('section') ?? $student->section }}"
                                class="form-control {{ hasError('section') }}" placeholder="Section">
                            @error('section')
                                {!! displayError($message) !!}
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Roll</label>
                            <input type="text" name="roll" value="{{ old('roll') ?? $student->roll }}"
                                class="form-control {{ hasError('roll') }}" placeholder="Roll">
                            @error('roll')
                                {!! displayError($message) !!}
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Shift</label>
                            <input type="text" name="shift" value="{{ old('shift') ?? $student->shift }}"
                                class="form-control {{ hasError('shift') }}" placeholder="Shift">
                            @error('shift')
                                {!! displayError($message) !!}
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Medium</label>
                            <input type="text" name="medium" value="{{ old('medium') ?? $student->medium }}"
                                class="form-control {{ hasError('medium') }}" placeholder="Medium">
                            @error('medium')
                                {!! displayError($message) !!}
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label">Group</label>
                            <input type="text" name="group" value="{{ old('group') ?? $student->group }}"
                                class="form-control {{ hasError('group') }}" placeholder="Group">
                            @error('group')
                                {!! displayError($message) !!}
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
