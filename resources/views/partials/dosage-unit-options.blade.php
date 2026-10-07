<option value="" @selected(empty($selected ?? null))>Select unit</option>
@foreach(['Pill', 'Syrup', 'Capsule', 'Drops', 'Cream', 'Injection'] as $unit)
    <option value="{{ $unit }}" @selected(($selected ?? null) === $unit)>{{ $unit }}</option>
@endforeach
