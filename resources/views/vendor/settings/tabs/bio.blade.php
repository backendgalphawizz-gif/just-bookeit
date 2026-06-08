<h2 class="vp-settings-panel-title">Bio / Description</h2>

<form method="POST" action="{{ route('vendor.settings.update') }}">
    @csrf
    <input type="hidden" name="tab" value="bio">

    <div class="vp-editor-toolbar">
        <button type="button" class="vp-editor-btn" data-editor-cmd="bold" title="Bold">B</button>
        <button type="button" class="vp-editor-btn" data-editor-cmd="italic" title="Italic"><em>I</em></button>
        <button type="button" class="vp-editor-btn" data-editor-cmd="underline" title="Underline"><u>U</u></button>
    </div>
    <textarea id="bio-editor" name="bio" class="vp-editor-area @error('bio') vp-textarea--error @enderror" maxlength="20000" data-vp-restrict="text" placeholder="Write about your business, experience, and specialties...">{{ old('bio', $vendor->bio) }}</textarea>
    @error('bio')<p class="vp-field-error">{{ $message }}</p>@enderror

    <div class="vp-settings-panel-foot">
        <button type="submit" class="vp-btn vp-btn--primary">Update</button>
    </div>
</form>
