export function profileCollection(initialItems, emptyItem, options = {}) {
    const copy = (value) => JSON.parse(JSON.stringify(value));

    return {
        items: Array.isArray(initialItems) ? initialItems : [],
        emptyItem,
        options,
        draft: {},
        dateParts: {},
        editing: false,
        editingIndex: null,
        validationMessage: '',

        add() {
            this.draft = copy(this.emptyItem);
            this.dateParts = {};
            this.editingIndex = null;
            this.editing = true;
            this.validationMessage = '';
        },

        edit(index) {
            this.draft = copy(this.items[index]);
            this.dateParts = {};
            this.editingIndex = index;
            this.editing = true;
            this.validationMessage = '';
        },

        cancel() {
            this.editing = false;
            this.editingIndex = null;
            this.draft = {};
            this.dateParts = {};
            this.validationMessage = '';
        },

        remove(index) {
            this.items.splice(index, 1);
            if (this.editingIndex === index) this.cancel();
        },

        isValueUsed(field, value) {
            return this.items.some((item, index) => item[field] === value && index !== this.editingIndex);
        },

        save(requiredFields, conditionalFields = []) {
            const missingRequiredValue = [...requiredFields, ...conditionalFields]
                .some((field) => !String(this.draft[field] ?? '').trim());

            if (missingRequiredValue) {
                this.validationMessage = this.options.requiredMessage || 'Complete the required fields.';
                return;
            }

            const dateOrderFields = this.options.dateOrderFields;
            if (dateOrderFields && !this.draft.is_current) {
                const [startField, endField] = dateOrderFields;
                const startDate = String(this.draft[startField] || '');
                const endDate = String(this.draft[endField] || '');
                if (startDate && endDate && endDate < startDate) {
                    this.validationMessage = this.options.dateOrderMessage || 'End date must be after the start date.';
                    return;
                }
            }

            if (this.options.uniqueField && this.isValueUsed(this.options.uniqueField, this.draft[this.options.uniqueField])) {
                this.validationMessage = this.options.duplicateMessage || 'This option has already been added.';
                return;
            }

            const item = copy(this.draft);
            if (this.editingIndex === null) {
                this.items.push(item);
            } else {
                this.items.splice(this.editingIndex, 1, item);
            }
            this.cancel();
        },

        proficiencyLabel(value) {
            return this.options.proficiencyLabels?.[value] || value;
        },

        dateMonth(value) {
            return String(value || '').split('-')[1] || '';
        },

        dateYear(value) {
            return String(value || '').split('-')[0] || '';
        },

        selectedDateMonth(field) {
            return this.dateParts[field]?.month ?? this.dateMonth(this.draft[field]);
        },

        selectedDateYear(field) {
            return this.dateParts[field]?.year ?? this.dateYear(this.draft[field]);
        },

        setDatePart(field, part, value) {
            const existing = this.dateParts[field] || {
                month: this.dateMonth(this.draft[field]),
                year: this.dateYear(this.draft[field]),
            };
            this.dateParts[field] = { ...existing, [part]: value };
            const { month, year } = this.dateParts[field];
            this.draft[field] = month && year ? `${year}-${month}` : '';
        },

        clearDate(field) {
            this.dateParts[field] = { month: '', year: '' };
            this.draft[field] = '';
        },

        dateRange(start, end, current) {
            const format = (value) => {
                if (!value) return '';
                const [year, month] = value.split('-');
                if (!month) return year;

                return new Intl.DateTimeFormat(document.documentElement.lang || 'en', {
                    month: 'short',
                    year: 'numeric',
                    timeZone: 'UTC',
                }).format(new Date(Date.UTC(Number(year), Number(month) - 1, 1)));
            };

            return `${format(start)} – ${current ? this.options.present : format(end)}`;
        },
    };
}
