@php
    $selectedPermissionNames = collect($selectedPermissions ?? [])->map(
        fn ($permission) => is_string($permission) ? $permission : $permission->name
    );
    $matrixReadonly = $readonly ?? false;
    $permissionCount = $permissionGroups->sum(fn ($items) => $items->count());
@endphp

<div class="permission-matrix" data-permission-matrix data-readonly="{{ $matrixReadonly ? 'true' : 'false' }}">
    <div class="card border rounded-3 shadow-none mb-4">
        <div class="card-body p-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div>
                    <h6 class="mb-1">Permission Selection</h6>
                    <div class="text-secondary fs-14">
                        <strong data-selected-permission-count>0</strong> of {{ $permissionCount }} permissions selected
                    </div>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <div class="position-relative">
                        <span class="material-symbols-outlined position-absolute top-50 translate-middle-y text-secondary fs-18" style="left: 12px;">search</span>
                        <input type="search" class="form-control form-control-sm ps-5" style="min-width: 230px;" placeholder="Find a permission" data-permission-search>
                    </div>
                    @unless($matrixReadonly)
                        <button type="button" class="btn btn-primary btn-sm" data-permission-control="select-all">
                            <span class="material-symbols-outlined align-middle fs-18">select_all</span>
                            Select All Permissions
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-permission-control="clear-all">
                            <span class="material-symbols-outlined align-middle fs-18">deselect</span>
                            Clear All
                        </button>
                    @endunless
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3" data-permission-groups>
        @foreach($permissionGroups as $groupName => $items)
            @php
                $groupId = $matrixId.'-'.\Illuminate\Support\Str::slug($groupName);
                $hasOptionalPermissions = $items->contains(
                    fn ($item) => $item['permission']->name !== 'dashboard.view'
                );
            @endphp
            <div class="col-xl-6 permission-group-column" data-permission-group-column>
                <div class="border rounded-3 h-100 overflow-hidden" data-permission-group>
                    <div class="d-flex align-items-center justify-content-between gap-3 bg-light px-3 py-3 border-bottom">
                        <div>
                            <div class="fw-semibold">{{ $groupName }}</div>
                            <small class="text-secondary">
                                <span data-group-selected-count>0</span> of {{ $items->count() }} selected
                            </small>
                        </div>
                        @if(! $matrixReadonly && $hasOptionalPermissions)
                            <label class="form-check mb-0 text-nowrap">
                                <input type="checkbox" class="form-check-input permission-group-toggle" aria-controls="{{ $groupId }}">
                                <span class="form-check-label fs-13">Select group</span>
                            </label>
                        @endif
                    </div>
                    <div class="p-3" id="{{ $groupId }}">
                        @foreach($items as $item)
                            @php
                                $permission = $item['permission'];
                                $isRequired = $permission->name === 'dashboard.view';
                                $isChecked = $isRequired || $selectedPermissionNames->contains($permission->name);
                                [$module, $action] = array_pad(explode('.', $permission->name, 2), 2, '');
                                $searchText = strtolower($permission->name.' '.$item['description']);
                            @endphp
                            <div class="permission-option {{ $loop->last ? '' : 'border-bottom pb-2 mb-2' }}" data-permission-option data-search-text="{{ $searchText }}">
                                <label class="form-check mb-0 w-100">
                                    <input
                                        type="checkbox"
                                        name="permissions[]"
                                        value="{{ $permission->name }}"
                                        class="form-check-input permission-checkbox"
                                        data-permission-module="{{ $module }}"
                                        data-permission-action="{{ $action }}"
                                        {{ $isChecked ? 'checked' : '' }}
                                        {{ ($matrixReadonly || $isRequired) ? 'disabled' : '' }}
                                    >
                                    <span class="form-check-label d-block">
                                        <span class="fw-medium">{{ \Illuminate\Support\Str::headline(str_replace('.', ' ', $permission->name)) }}</span>
                                        @if($isRequired)
                                            <span class="badge bg-primary-subtle text-primary ms-1">Required</span>
                                        @endif
                                        <small class="text-secondary d-block mt-1">{{ $item['description'] }}</small>
                                    </span>
                                </label>
                                @if($isRequired && ! $matrixReadonly)
                                    <input type="hidden" name="permissions[]" value="{{ $permission->name }}">
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="alert alert-light border mt-3 mb-0 py-2 fs-14 d-none" data-permission-no-results>
        No permissions match your search.
    </div>
</div>
