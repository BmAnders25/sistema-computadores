<div class="d-flex align-items-center bg-{{ $theme }} text-white p-3 rounded mb-3 shadow-sm">
    @if($icon)
        <div class="me-3">
            <i class="{{ $icon }} fa-2x"></i>
        </div>
    @endif
    <div>
        <div class="fw-bold">{{ $title }}</div>
        <div>{{ $text }}</div>
    </div>
</div>