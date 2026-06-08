<h2 class="vp-settings-panel-title">Bank Details</h2>

<form method="POST" action="{{ route('vendor.settings.update') }}">
    @csrf
    <input type="hidden" name="tab" value="bank">

    <div class="vp-field">
        <label class="vp-label" for="account_name">Account Holder Name</label>
        <input id="account_name" type="text" name="account_name" class="vp-input @error('account_name') vp-input--error @enderror" value="{{ old('account_name', $vendor->account_name) }}" placeholder="Name as per bank records" maxlength="255" data-vp-restrict="person-name">
        @error('account_name')<p class="vp-field-error">{{ $message }}</p>@enderror
    </div>
    <div class="vp-field">
        <label class="vp-label" for="account_no">Account Number</label>
        <input id="account_no" type="text" name="account_no" class="vp-input @error('account_no') vp-input--error @enderror" value="{{ old('account_no', $vendor->account_number) }}" placeholder="Digits only" inputmode="numeric" maxlength="20" data-vp-restrict="account-number">
        @error('account_no')<p class="vp-field-error">{{ $message }}</p>@enderror
    </div>
    <div class="vp-field">
        <label class="vp-label" for="bank_name">Bank Name</label>
        <input id="bank_name" type="text" name="bank_name" class="vp-input @error('bank_name') vp-input--error @enderror" value="{{ old('bank_name', $vendor->bank_name) }}" placeholder="State Bank of India" maxlength="255" data-vp-restrict="title">
        @error('bank_name')<p class="vp-field-error">{{ $message }}</p>@enderror
    </div>
    <div class="vp-field">
        <label class="vp-label" for="ifsc_code">IFSC Code</label>
        <input id="ifsc_code" type="text" name="ifsc_code" class="vp-input @error('ifsc_code') vp-input--error @enderror" value="{{ old('ifsc_code', $vendor->ifsc_code) }}" placeholder="SBIN0000001" maxlength="11" data-vp-restrict="ifsc">
        <p class="vp-field-hint">11 characters, e.g. SBIN0000001</p>
        @error('ifsc_code')<p class="vp-field-error">{{ $message }}</p>@enderror
    </div>

    <div class="vp-field">
        <label class="vp-label">Account Type</label>
        <div class="vp-account-type">
            <label>
                <input type="radio" name="account_type" value="savings" @checked(old('account_type', $vendor->account_type) === 'savings' || ! $vendor->account_type)>
                <span>Saving</span>
            </label>
            <label>
                <input type="radio" name="account_type" value="current" @checked(old('account_type', $vendor->account_type) === 'current')>
                <span>Current</span>
            </label>
        </div>
        @error('account_type')<p class="vp-field-error">{{ $message }}</p>@enderror
    </div>

    <div class="vp-settings-panel-foot">
        <button type="submit" class="vp-btn vp-btn--primary">Save</button>
    </div>
</form>
