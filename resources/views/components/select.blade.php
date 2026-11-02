@props(['options' => [], 'selected' => null, 'placeholder' => null])

<select {{ $attributes->merge(['class' => 'rounded-xl border-slate-200 bg-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500']) }}>
    @if ($placeholder)
        <option value="">{{ $placeholder }}</option>
    @endif

    @foreach ($options as $value => $label)
        <option value="{{ $value }}" @selected((string) $selected === (string) $value)>{{ $label }}</option>
    @endforeach
</select>
