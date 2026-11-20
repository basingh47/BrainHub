{{-- BrainHub logo: two brain halves + a hub (centre dot) linked to six nodes.
     Colours come from currentColor, so set them with a Tailwind text-* class. --}}
<svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" {{ $attributes }}>
    {{-- Left and right brain halves (the right one is the left mirrored: x → 64 - x) --}}
    <path d="M31 10C26 6 18 7 16 13C10 13 6 19 9 25C4 29 5 37 10 40C8 46 13 53 20 52C22 57 29 58 31 54Z"/>
    <path d="M33 10C38 6 46 7 48 13C54 13 58 19 55 25C60 29 59 37 54 40C56 46 51 53 44 52C42 57 35 58 33 54Z"/>
    {{-- Spokes from the hub to each node --}}
    <path stroke-width="2.2" d="M32 32L21 22M32 32L43 22M32 32L21 42M32 32L43 42M32 32L16 32M32 32L48 32"/>
    {{-- Hub (big dot) and nodes (small dots) --}}
    <g fill="currentColor" stroke="none">
        <circle cx="32" cy="32" r="4.5"/>
        <circle cx="21" cy="22" r="2.6"/>
        <circle cx="43" cy="22" r="2.6"/>
        <circle cx="21" cy="42" r="2.6"/>
        <circle cx="43" cy="42" r="2.6"/>
        <circle cx="16" cy="32" r="2.6"/>
        <circle cx="48" cy="32" r="2.6"/>
    </g>
</svg>
