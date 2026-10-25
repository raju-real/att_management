{{--
    Institute dropdown (select2) used on every search / report / form panel.

    Super admin  → normal select of all institutes ("All Institutes" unless required).
    Institute    → its own institute, pre-selected and read-only (it cannot pick
                   another one; the server forces it anyway via selectedInstituteId()).

    @include('partials.institute_select', [
        'required'   => false,          // true on add/edit forms
        'selected'   => $model->institute_id ?? null,
        'autoSubmit' => false,          // submit the filter form on change
        'class'      => 'form-control', // e.g. 'form-control form-control-sm'
        'id'         => 'instituteSelect',
    ])
--}}
@php
    $iName     = $name ?? 'institute_id';
    $iRequired = $required ?? false;
    $iSelected = (string) (old($iName) ?? ($selected ?? request($iName)));
    $iClass    = $class ?? 'form-control';
    $iId       = $id ?? 'instituteSelect';
    $iHolder   = $iRequired ? '-- Select Institute --' : 'All Institutes';
@endphp
@if (authInstituteId())
    <select id="{{ $iId }}" class="{{ $iClass }} institute-select2" disabled data-locked="1">
        <option value="{{ authInstituteId() }}" selected>{{ authUser()->name }}</option>
    </select>
    <input type="hidden" name="{{ $iName }}" value="{{ authInstituteId() }}">
@else
    <select name="{{ $iName }}" id="{{ $iId }}" class="{{ $iClass }} institute-select2 {{ hasError($iName) }}"
            data-placeholder="{{ $iHolder }}" data-allow-clear="{{ $iRequired ? 0 : 1 }}"
            @if ($autoSubmit ?? false) onchange="this.form.submit()" @endif>
        <option value="">{{ $iHolder }}</option>
        @foreach (instituteOptions() as $inst)
            <option value="{{ $inst->id }}" {{ $iSelected === (string) $inst->id ? 'selected' : '' }}>
                {{ $inst->name }}{{ $inst->status !== 'active' ? ' [inactive]' : '' }}
            </option>
        @endforeach
    </select>
@endif
