@php($editing = isset($statement))

<div class="row g-4">
    <div class="col-md-6">
        <label class="form-label">Account <span class="text-danger">*</span></label>
        <select name="account_id" class="form-select" required>
            <option value="">Select Account</option>
            @foreach($accounts as $account)
                <option value="{{ $account->id }}" {{ old('account_id', $statement->account_id ?? null) === $account->id ? 'selected' : '' }}>
                    {{ $account->account_name }} — {{ $account->bank_name }} ({{ $account->account_number }})
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">Statement From <span class="text-danger">*</span></label>
        <input type="date" id="statement_from" name="statement_from" class="form-control" value="{{ old('statement_from', isset($statement) ? $statement->statement_from->format('Y-m-d') : '') }}" required>
    </div>
    <div class="col-md-3">
        <label class="form-label">Statement To <span class="text-danger">*</span></label>
        <input type="date" id="statement_to" name="statement_to" class="form-control" value="{{ old('statement_to', isset($statement) ? $statement->statement_to->format('Y-m-d') : '') }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">Opening Balance (₦) <span class="text-danger">*</span></label>
        <input type="number" step="0.01" name="opening_balance" class="form-control" value="{{ old('opening_balance', $statement->opening_balance ?? 0) }}" required>
        <small class="text-muted">Negative balances are allowed for overdraft accounts.</small>
    </div>
    <div class="col-md-6">
        <label class="form-label">Closing Balance (₦) <span class="text-danger">*</span></label>
        <input type="number" step="0.01" name="closing_balance" class="form-control" value="{{ old('closing_balance', $statement->closing_balance ?? 0) }}" required>
    </div>
    <div class="col-md-12">
        <label class="form-label">Supporting Statement File <span class="text-secondary">(optional)</span></label>
        <input type="file" name="file" class="form-control" accept=".pdf,.csv,.xlsx,.xls,.jpg,.jpeg,.png">
        <small class="text-muted">PDF, Excel, CSV or image up to 10 MB. The file is stored securely and is not used to create transaction lines.</small>
        @if($editing && $statement->hasAttachment())
            <div class="d-flex align-items-center gap-3 mt-2">
                <a href="{{ route('bank-statements.download', $statement) }}" class="text-decoration-none">
                    <i class="material-symbols-outlined align-middle fs-18">attach_file</i>
                    {{ $statement->file_name }}
                </a>
                <label class="form-check mb-0">
                    <input type="checkbox" name="remove_file" value="1" class="form-check-input">
                    <span class="form-check-label text-danger">Remove attachment</span>
                </label>
            </div>
        @endif
    </div>
    <div class="col-md-12">
        <label class="form-label">Notes</label>
        <textarea name="notes" class="form-control" rows="3" placeholder="Optional statement notes or bank contact details">{{ old('notes', $statement->notes ?? '') }}</textarea>
    </div>
    <div class="col-12 d-flex gap-2">
        <button type="submit" class="btn btn-primary px-4">{{ $editing ? 'Update Statement' : 'Save Statement' }}</button>
        <a href="{{ $editing ? route('bank-statements.show', $statement) : route('bank-statements.index') }}" class="btn btn-outline-secondary">Cancel</a>
    </div>
</div>

@push('scripts')
    <script>
        document.getElementById('statement_from')?.addEventListener('change', function () {
            const toInput = document.getElementById('statement_to');
            if (!this.value || toInput.value) return;

            const start = new Date(this.value + 'T00:00:00');
            const end = new Date(start.getFullYear(), start.getMonth() + 1, 0);
            const year = end.getFullYear();
            const month = String(end.getMonth() + 1).padStart(2, '0');
            const day = String(end.getDate()).padStart(2, '0');
            toInput.value = `${year}-${month}-${day}`;
        });
    </script>
@endpush
