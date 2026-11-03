{{--
    Repeatable rows (education / class teacher / subject teacher) posted as
    {{ $field }}[i][column]. Blank rows are dropped on save (Teacher::cleanRows).
--}}
<div class="card admin-card mt-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0"><i class="fas {{ $icon }} mr-2"></i> {{ $title }}</h5>
        <button type="button" class="btn btn-sm btn-primary-admin text-white add-row-btn" data-target="rows-{{ $field }}">
            <i class="fas fa-plus mr-1"></i> Add Row
        </button>
    </div>
    <div class="card-body p-2">
        <div class="table-responsive">
            <table class="table table-sm table-bordered mb-0" id="rows-{{ $field }}" data-field="{{ $field }}">
                <thead class="thead-light">
                    <tr>
                        @foreach ($columns as $label)
                            <th>{{ $label }}</th>
                        @endforeach
                        <th style="width:40px"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $i => $row)
                        <tr>
                            @foreach ($columns as $key => $label)
                                <td>
                                    <input type="text" class="form-control form-control-sm {{ hasError("$field.$i.$key") }}"
                                        name="{{ $field }}[{{ $i }}][{{ $key }}]" data-key="{{ $key }}"
                                        value="{{ $row[$key] ?? '' }}" placeholder="{{ $label }}">
                                </td>
                            @endforeach
                            <td class="text-center align-middle">
                                <a href="javascript:void(0)" class="text-danger remove-row-btn" {!! tooltip('Remove row') !!}><i class="fas fa-times"></i></a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @error($field) {!! displayError($message) !!} @enderror
    </div>
</div>
