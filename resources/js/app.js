import './bootstrap';

import Alpine from 'alpinejs';
import ApexCharts from 'apexcharts';

window.Alpine = Alpine;
window.ApexCharts = ApexCharts;

/**
 * Komponen form transaksi (Sales Order / Purchase Order) dengan baris item dinamis
 * & kalkulasi subtotal / DPP / PPN opsional / total secara langsung.
 *
 * @param {Object} cfg
 * @param {Array}  cfg.products     [{id, name, price, unit}]
 * @param {Array}  cfg.items        baris awal (edit)
 * @param {String} cfg.priceField   'sell_price' | 'cost_price'
 * @param {Boolean} cfg.isTaxable
 * @param {Number} cfg.ppnRate
 * @param {Number} cfg.discount     diskon dokumen
 * @param {Boolean} cfg.withReason  tampilkan kolom alasan diskon (SO)
 */
window.orderForm = function (cfg) {
    const blankRow = () => ({ product_id: '', qty: 1, price: 0, discount: 0, discount_reason: '' });

    return {
        products: cfg.products || [],
        priceField: cfg.priceField,
        withReason: !!cfg.withReason,
        isTaxable: !!cfg.isTaxable,
        ppnRate: Number(cfg.ppnRate) || 0,
        headerDiscount: Number(cfg.discount) || 0,
        items: (cfg.items && cfg.items.length ? cfg.items.map((i) => ({
            product_id: String(i.product_id),
            qty: Number(i.qty),
            price: Number(i.price),
            discount: Number(i.discount) || 0,
            discount_reason: i.discount_reason || '',
        })) : [blankRow()]),

        addRow() {
            this.items.push(blankRow());
        },
        removeRow(idx) {
            this.items.splice(idx, 1);
            if (this.items.length === 0) this.addRow();
        },
        onProductChange(row) {
            const p = this.products.find((x) => String(x.id) === String(row.product_id));
            if (p) row.price = Number(p.price);
        },
        productUnit(row) {
            const p = this.products.find((x) => String(x.id) === String(row.product_id));
            return p ? p.unit : '';
        },
        rowSubtotal(row) {
            return Math.max(0, (Number(row.qty) || 0) * (Number(row.price) || 0) - (Number(row.discount) || 0));
        },
        get subtotal() {
            return this.items.reduce((s, r) => s + this.rowSubtotal(r), 0);
        },
        get dpp() {
            return Math.max(0, this.subtotal - (Number(this.headerDiscount) || 0));
        },
        get ppn() {
            return this.isTaxable ? Math.round((this.dpp * this.ppnRate) / 100 * 100) / 100 : 0;
        },
        get total() {
            return this.dpp + this.ppn;
        },
        rp(n) {
            return 'Rp ' + (Number(n) || 0).toLocaleString('id-ID', { maximumFractionDigits: 2 });
        },
    };
};

/**
 * Tanggal dokumen + jatuh tempo. Jatuh tempo diisi otomatis dari termin
 * supplier/pelanggan, tapi tetap bisa ditimpa manual oleh user.
 *
 * @param {Object} cfg
 * @param {Object} cfg.terms     {partyId: termin_hari}
 * @param {String} cfg.partyId   supplier/pelanggan terpilih
 * @param {String} cfg.date      tanggal dokumen (YYYY-MM-DD)
 * @param {String} cfg.dueDate   jatuh tempo awal (kosong = ikut termin)
 */
window.dueDatePicker = function (cfg) {
    return {
        terms: cfg.terms || {},
        partyId: cfg.partyId || '',
        date: cfg.date || '',
        dueDate: cfg.dueDate || '',
        // Sekali user mengetik jatuh tempo sendiri, jangan ditimpa lagi oleh termin.
        manual: !!cfg.dueDate,

        init() {
            this.$watch('dueDate', (val) => {
                if (val !== this.termDueDate()) this.manual = true;
            });
            if (!this.dueDate) this.applyTerm();
        },
        term() {
            const t = this.terms[this.partyId];
            return t === undefined ? null : Number(t);
        },
        termDueDate() {
            const term = this.term();
            if (term === null || !this.date) return '';
            const d = new Date(this.date + 'T00:00:00');
            d.setDate(d.getDate() + term);
            return d.toISOString().slice(0, 10);
        },
        applyTerm() {
            if (this.manual) return;
            this.dueDate = this.termDueDate();
        },
        daysBetween() {
            if (!this.date || !this.dueDate) return null;
            const a = new Date(this.date + 'T00:00:00');
            const b = new Date(this.dueDate + 'T00:00:00');
            return Math.round((b - a) / 86400000);
        },
        termLabel() {
            const term = this.term();
            const days = this.daysBetween();
            const daysInfo = days !== null ? `Tempo ${days} hari` : '';

            if (term === null && !daysInfo) return 'Ikut termin setelah pilih supplier/pelanggan.';
            if (term === null) return daysInfo + '.';

            if (days !== null && days !== term) {
                return `${daysInfo} (termin ${term} hari).`;
            }
            if (this.manual) return daysInfo ? `${daysInfo} · diisi manual.` : `Diisi manual · termin ${term} hari.`;
            return daysInfo ? `${daysInfo} · otomatis dari termin.` : `Otomatis dari termin ${term} hari.`;
        },
    };
};

Alpine.start();
