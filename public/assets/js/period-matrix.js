function periodMatrix(marketplaces, existingEntries) {
    return {
        rows: marketplaces.map(function (m) {
            var existing = existingEntries[m.id] || { value_cents: 0, orders_count: 0 };
            return {
                id: m.id,
                name: m.name,
                color: m.color,
                valueCents: existing.value_cents || 0,
                valueDisplay: formatCentsToBrl(existing.value_cents || 0),
                ordersCount: existing.orders_count || 0,
            };
        }),
        onValueInput: function (row) {
            var digits = (row.valueDisplay || '').replace(/\D/g, '');
            row.valueCents = digits === '' ? 0 : parseInt(digits, 10);
            row.valueDisplay = formatCentsToBrl(row.valueCents);
        },
        get totalCents() {
            return this.rows.reduce(function (sum, r) { return sum + (r.valueCents || 0); }, 0);
        },
        get totalOrders() {
            return this.rows.reduce(function (sum, r) { return sum + (parseInt(r.ordersCount, 10) || 0); }, 0);
        },
        get totalDisplay() {
            return formatCentsToBrl(this.totalCents);
        },
    };
}

function formatCentsToBrl(cents) {
    var value = (cents / 100).toFixed(2);
    var parts = value.split('.');
    var intPart = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    return 'R$ ' + intPart + ',' + parts[1];
}

document.addEventListener('DOMContentLoaded', function () {
    var startDate = document.getElementById('start_date');
    var referenceMonth = document.getElementById('reference_month');
    if (!startDate || !referenceMonth) {
        return;
    }
    startDate.addEventListener('change', function () {
        if (!referenceMonth.value && startDate.value) {
            referenceMonth.value = startDate.value.slice(0, 7);
        }
    });
});
