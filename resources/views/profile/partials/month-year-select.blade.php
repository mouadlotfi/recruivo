@php
    $disabledExpression = $disabledExpression ?? 'false';
@endphp
<div>
    <label class="{{ $labelClass }}">{{ $label }}</label>
    <div class="mt-1 grid grid-cols-2 gap-2">
        <select
            data-date-month="{{ $field }}"
            :value="selectedDateMonth('{{ $field }}')"
            @change="setDatePart('{{ $field }}', 'month', $event.target.value)"
            :disabled="{{ $disabledExpression }}"
            class="{{ $inputClass }}"
        >
            <option value="">{{ __('profile.month') }}</option>
            @foreach($months as $value => $month)
                <option value="{{ $value }}">{{ $month }}</option>
            @endforeach
        </select>
        <select
            data-date-year="{{ $field }}"
            data-year-policy="{{ $yearPolicy }}"
            :value="selectedDateYear('{{ $field }}')"
            @change="setDatePart('{{ $field }}', 'year', $event.target.value)"
            :disabled="{{ $disabledExpression }}"
            class="{{ $inputClass }}"
        >
            <option value="">{{ __('profile.year') }}</option>
            @foreach($years as $year)
                <option value="{{ $year }}">{{ $year }}</option>
            @endforeach
        </select>
    </div>
</div>
