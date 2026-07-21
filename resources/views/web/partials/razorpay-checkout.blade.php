@php
    /** @var array<string, mixed>|null $razorpayOptions */
    $razorpayOptions = $razorpayOptions ?? null;
@endphp

@if (! empty($razorpayOptions))
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
(function () {
    const form = document.getElementById('jbw-payment-form');
    if (!form || typeof Razorpay === 'undefined') return;

    const options = @json($razorpayOptions);
    const submitBtn = form.querySelector('[type="submit"]');

    form.addEventListener('submit', function (event) {
        const methodInput = form.querySelector('input[name="payment_method"]:checked');
        const method = methodInput ? methodInput.value : '';

        if (method === 'cod') {
            return;
        }

        event.preventDefault();

        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.dataset.originalText = submitBtn.textContent;
            submitBtn.textContent = 'Opening Razorpay…';
        }

        const checkout = new Razorpay({
            ...options,
            handler: function (response) {
                form.querySelector('[name="razorpay_payment_id"]').value = response.razorpay_payment_id || '';
                form.querySelector('[name="razorpay_order_id"]').value = response.razorpay_order_id || '';
                form.querySelector('[name="razorpay_signature"]').value = response.razorpay_signature || '';
                form.submit();
            },
            modal: {
                ondismiss: function () {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = submitBtn.dataset.originalText || 'Pay now';
                    }
                }
            }
        });

        checkout.on('payment.failed', function () {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = submitBtn.dataset.originalText || 'Pay now';
            }
            alert('Payment failed. Please try again.');
        });

        checkout.open();
    });
})();
</script>
@endif
