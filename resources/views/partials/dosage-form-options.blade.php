<option value="" @selected(empty($selected ?? null))>Select form</option>
@foreach(['Capsule', 'Syrup', 'Drops', 'Cream', 'Injection'] as $form)
    <option value="{{ $form }}" @selected(($selected ?? null) === $form)>{{ $form }}</option>
@endforeach
