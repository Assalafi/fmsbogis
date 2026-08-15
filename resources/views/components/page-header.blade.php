@props(['title', 'breadcrumbs' => [], 'actions' => null])

<div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-4">
    <div>
        <h3 class="mb-1">{{ $title }}</h3>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 fs-14">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                @foreach($breadcrumbs as $label => $url)
                    @if($url)
                        <li class="breadcrumb-item"><a href="{{ $url }}" class="text-decoration-none">{{ $label }}</a></li>
                    @else
                        <li class="breadcrumb-item active" aria-current="page">{{ $label }}</li>
                    @endif
                @endforeach
            </ol>
        </nav>
    </div>
    <div class="d-flex flex-wrap gap-2">
        {{ $slot }}
    </div>
</div>
