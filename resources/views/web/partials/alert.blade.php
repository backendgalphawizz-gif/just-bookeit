@if (session('success'))
    <div class="jbw-alert jbw-alert--success" role="alert">{{ session('success') }}</div>
@endif
@if (session('error'))
    <div class="jbw-alert jbw-alert--error" role="alert">{{ session('error') }}</div>
@endif
@if (session('info') && empty($skipInfo))
    <div class="jbw-alert jbw-alert--info" role="alert">{{ session('info') }}</div>
@endif
@if ($errors->any())
    <div class="jbw-alert jbw-alert--error" role="alert">
        <ul class="jbw-alert-list">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
