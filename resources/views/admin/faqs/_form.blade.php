@php
    $faq = $faq ?? null;
    $audience = old('audience', $faq?->audience ?? ($audience ?? 'user'));
@endphp

<x-admin.form-select label="App" name="audience" :required="true">
    @foreach (\App\Models\Faq::AUDIENCES as $option)
        <option value="{{ $option }}" @selected($audience === $option)>{{ \App\Models\Faq::audienceLabel($option) }}</option>
    @endforeach
</x-admin.form-select>

@include('admin.partials.form-input', [
    'label' => 'Question',
    'name' => 'question',
    'restrict' => 'text',
    'value' => old('question', $faq?->question),
    'required' => true,
    'full' => true,
])

@include('admin.partials.form-input', [
    'label' => 'Answer',
    'name' => 'answer',
    'type' => 'textarea',
    'rows' => 8,
    'restrict' => 'text',
    'value' => old('answer', $faq?->answer),
    'required' => true,
    'full' => true,
])

@include('admin.partials.form-input', [
    'label' => 'Sort order',
    'name' => 'sort_order',
    'type' => 'number',
    'min' => '0',
    'max' => '9999',
    'value' => old('sort_order', $faq?->sort_order ?? 0),
])

<div class="jb-checkbox-row sm:col-span-2">
    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $faq?->is_active ?? true))>
    <label class="text-sm font-medium text-slate-700">Active</label>
</div>
