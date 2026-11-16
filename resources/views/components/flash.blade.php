{{--
    Flash message — shown once after a redirect.
    Controller: ->with('flash', ['type' => 'success|info|warning|danger', 'message' => '...'])
    Colour AND icon per type (icon helps colour-blind users).
--}}
@php
    $flash = session('flash');

    $styles = [
        'success' => ['box' => 'border-green-200 bg-green-50 text-green-800', 'icon' => '✓'],
        'info' => ['box' => 'border-blue-200 bg-blue-50 text-blue-800', 'icon' => 'ℹ'],
        'warning' => ['box' => 'border-amber-200 bg-amber-50 text-amber-800', 'icon' => '!'],
        'danger' => ['box' => 'border-red-200 bg-red-50 text-red-800', 'icon' => '✕'],
    ];
@endphp

@if ($flash)
    @php $style = $styles[$flash['type'] ?? 'info'] ?? $styles['info']; @endphp

    <div role="status" class="flex items-center gap-3 rounded-md border px-4 py-3 text-sm {{ $style['box'] }}">
        <span aria-hidden="true" class="font-bold">{{ $style['icon'] }}</span>
        <p>{{ $flash['message'] }}</p>
    </div>
@endif
