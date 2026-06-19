/**
 * Native date filter fields with min/max bounds (admin + vendor list filters).
 */
(function () {
    function isUsableFilterDate(value, minDate, maxDate) {
        if (! /^\d{4}-\d{2}-\d{2}$/.test(value)) {
            return false;
        }

        return value >= minDate && value <= maxDate;
    }

    function formatIsoDate(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');

        return `${year}-${month}-${day}`;
    }

    function clampIsoDate(value, minDate, maxDate) {
        if (! isUsableFilterDate(value, minDate, maxDate)) {
            if (value < minDate) {
                return minDate;
            }

            if (value > maxDate) {
                return maxDate;
            }
        }

        return value;
    }

    window.jbFilterDateRange = (minDate, maxDate, initialFrom = '', initialTo = '') => ({
        minDate,
        maxDate,
        from: initialFrom,
        to: initialTo,
        isUsableDate(value) {
            return isUsableFilterDate(value, this.minDate, this.maxDate);
        },
        maxForFrom() {
            if (this.isUsableDate(this.to)) {
                return this.to;
            }

            return this.maxDate;
        },
        minForTo() {
            if (this.isUsableDate(this.from)) {
                return this.from;
            }

            return this.minDate;
        },
        applyRange(from, to) {
            let nextFrom = clampIsoDate(from, this.minDate, this.maxDate);
            let nextTo = clampIsoDate(to, this.minDate, this.maxDate);

            if (nextTo < nextFrom) {
                nextTo = nextFrom;
            }

            this.from = nextFrom;
            this.to = nextTo;

            if (this.$refs.fromInput) {
                this.$refs.fromInput.value = nextFrom;
            }

            if (this.$refs.toInput) {
                this.$refs.toInput.value = nextTo;
            }
        },
        setLast7Days() {
            const today = new Date();
            const from = new Date(today);
            from.setDate(today.getDate() - 6);

            this.applyRange(formatIsoDate(from), formatIsoDate(today));
        },
        setThisMonth() {
            const today = new Date();
            const from = new Date(today.getFullYear(), today.getMonth(), 1);

            this.applyRange(formatIsoDate(from), formatIsoDate(today));
        },
        clearDates() {
            this.from = '';
            this.to = '';

            if (this.$refs.fromInput) {
                this.$refs.fromInput.value = '';
            }

            if (this.$refs.toInput) {
                this.$refs.toInput.value = '';
            }
        },
        syncFrom(event) {
            const value = event.target.value;

            if (value === '') {
                this.from = '';

                return;
            }

            if (! this.isUsableDate(value)) {
                return;
            }

            this.from = value;

            if (this.isUsableDate(this.to) && this.to < value && this.$refs.toInput) {
                this.$refs.toInput.value = value;
                this.to = value;
            }
        },
        syncTo(event) {
            const value = event.target.value;

            if (value === '') {
                this.to = '';

                return;
            }

            if (! this.isUsableDate(value)) {
                return;
            }

            if (this.isUsableDate(this.from) && value < this.from) {
                event.target.value = this.from;
                this.to = this.from;

                return;
            }

            this.to = value;
        },
    });

    window.jbFilterSingleDate = (minDate, maxDate) => ({
        minDate,
        maxDate,
    });

    /** @deprecated Use jbFilterDateRange with native type="date" inputs. */
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
