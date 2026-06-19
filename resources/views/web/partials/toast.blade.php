@if (session('success') || session('error') || (session('info') && empty($skipInfo)) || $errors->any())
    <div class="jbw-toast-stack" id="jbw-toast-stack" aria-live="polite" aria-atomic="true">
        @if (session('success'))
            <div class="jbw-toast jbw-toast--success" role="alert" data-toast>
                <span class="jbw-toast-icon" aria-hidden="true">✓</span>
                <p class="jbw-toast-text">{{ session('success') }}</p>
                <button type="button" class="jbw-toast-close" aria-label="Dismiss">&times;</button>
            </div>
        @endif
        @if (session('error'))
            <div class="jbw-toast jbw-toast--error" role="alert" data-toast>
                <span class="jbw-toast-icon" aria-hidden="true">!</span>
                <p class="jbw-toast-text">{{ session('error') }}</p>
                <button type="button" class="jbw-toast-close" aria-label="Dismiss">&times;</button>
            </div>
        @endif
        @if (session('info') && empty($skipInfo))
            <div class="jbw-toast jbw-toast--info" role="alert" data-toast>
                <span class="jbw-toast-icon" aria-hidden="true">i</span>
                <p class="jbw-toast-text">{{ session('info') }}</p>
                <button type="button" class="jbw-toast-close" aria-label="Dismiss">&times;</button>
            </div>
        @endif
        @if ($errors->any())
            <div class="jbw-toast jbw-toast--error" role="alert" data-toast>
                <span class="jbw-toast-icon" aria-hidden="true">!</span>
                <div class="jbw-toast-text">
                    <ul class="jbw-toast-list">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                <button type="button" class="jbw-toast-close" aria-label="Dismiss">&times;</button>
            </div>
        @endif
    </div>
@endif
