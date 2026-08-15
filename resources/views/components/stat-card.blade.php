@props(['label', 'value', 'icon', 'color' => 'primary'])

<div class="col-xl-3 col-sm-6 mb-4">
    <div class="card border-0 bg-white rounded-3 p-4 h-100">
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <span class="fs-14 text-secondary d-block mb-1">{{ $label }}</span>
                <h3 class="fs-20 mb-0">{{ $value }}</h3>
            </div>
            <div class="wh-45 lh-45 rounded-3 text-center bg-{{ $color }} bg-opacity-10">
                <i class="material-symbols-outlined text-{{ $color }}">{{ $icon }}</i>
            </div>
        </div>
    </div>
</div>
