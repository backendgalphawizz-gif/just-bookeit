/**
 * Text-based YYYY-MM-DD filter fields — avoids native date input year typing issues.
 */
(function () {
    function isCompleteDate(value) {
        return /^\d{4}-\d{2}-\d{2}$/.test(value);
    }

    function formatDateDigits(value) {
        const digits = String(value ?? '').replace(/\D/g, '').slice(0, 8);

        if (digits.length <= 4) {
            return digits;
        }

        if (digits.length <= 6) {
            return digits.slice(0, 4) + '-' + digits.slice(4);
        }

        return digits.slice(0, 4) + '-' + digits.slice(4, 6) + '-' + digits.slice(6);
    }

    function createRangeFilter(config) {
        return {
            minDate: config.minDate || '1970-01-01',
            from: config.from || '',
            to: config.to || '',
            formatDateInput(event) {
                const input = event.target;
                const formatted = formatDateDigits(input.value);

                if (input.value !== formatted) {
                    input.value = formatted;
                }
            },
            normalizeFrom(event) {
                this.formatDateInput(event);

                const raw = event.target.value.trim();
                this.from = raw;

                if (! isCompleteDate(raw)) {
                    return;
                }

                let value = raw < this.minDate ? this.minDate : raw;

                if (value !== raw) {
                    event.target.value = value;
                    this.from = value;
                }

                if (this.to && this.from && this.to < this.from && this.$refs.toInput) {
                    this.$refs.toInput.value = this.from;
                    this.to = this.from;
                }
            },
            normalizeTo(event) {
                this.formatDateInput(event);

                const raw = event.target.value.trim();
                this.to = raw;

                if (! isCompleteDate(raw)) {
                    return;
                }

                let value = raw < this.minDate ? this.minDate : raw;

                if (this.from && value < this.from) {
                    value = this.from;
                }

                if (value !== raw) {
                    event.target.value = value;
                }

                this.to = value;
            },
        };
    }

    function createSingleFilter(config) {
        return {
            minDate: config.minDate || '1970-01-01',
            value: config.value || '',
            formatDateInput(event) {
                const input = event.target;
                const formatted = formatDateDigits(input.value);

                if (input.value !== formatted) {
                    input.value = formatted;
                }
            },
            normalize(event) {
                this.formatDateInput(event);

                const raw = event.target.value.trim();
                this.value = raw;

                if (! isCompleteDate(raw)) {
                    return;
                }

                if (raw < this.minDate) {
                    event.target.value = this.minDate;
                    this.value = this.minDate;
                }
            },
        };
    }

    document.addEventListener('alpine:init', () => {
        window.Alpine.data('jbDateRangeFilter', createRangeFilter);
        window.Alpine.data('jbDateFilterField', createSingleFilter);
    });
})();
