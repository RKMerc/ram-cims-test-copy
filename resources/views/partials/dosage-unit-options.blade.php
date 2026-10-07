<option value="" @selected(empty($selected ?? null))>Select unit</option>
@foreach(['Liter (L)', 'Milliliter (mL)', 'Gram (g)', 'Milligram (mg)', 'Pieces / Pcs'] as $unit)
    <option value="{{ $unit }}" @selected(($selected ?? null) === $unit)>{{ $unit }}</option>
@endforeach
