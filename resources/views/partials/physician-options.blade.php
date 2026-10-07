<option value="" disabled @selected(empty($selected ?? null))>Select attending physician</option>
@foreach(\App\Support\ClinicRoster::groups() as $group)
    <optgroup label="{{ $group['role'] }}">
        @foreach($group['people'] as $person)
            <option value="{{ $person['name'] }}" @selected(($selected ?? null) === $person['name'])>{{ $person['name'] }}</option>
        @endforeach
    </optgroup>
@endforeach
