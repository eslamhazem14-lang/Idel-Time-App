import Alpine from 'alpinejs';

/**
 * Visual countdown for a claim. The server is authoritative: expiresAt comes
 * from the database and we correct for clock skew using the server's "now".
 */
Alpine.data('countdown', (expiresAt, serverNow, totalSeconds) => ({
    remaining: 0,
    total: totalSeconds,
    offset: 0,
    timer: null,
    init() {
        this.offset = new Date(serverNow).getTime() - Date.now();
        this.tick();
        this.timer = setInterval(() => this.tick(), 1000);
    },
    destroy() { clearInterval(this.timer); },
    tick() {
        const now = Date.now() + this.offset;
        this.remaining = Math.max(0, Math.floor((new Date(expiresAt).getTime() - now) / 1000));
    },
    get display() {
        const m = Math.floor(this.remaining / 60);
        const s = this.remaining % 60;
        return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
    },
    get percent() { return this.total ? Math.max(0, Math.min(100, (this.remaining / this.total) * 100)) : 0; },
    get urgent() { return this.remaining <= 60; },
    get expired() { return this.remaining <= 0; },
}));

/** Autosaves the answer form as a server-side draft (used by the "submit draft on expiry" policy). */
Alpine.data('draftSaver', (url) => ({
    status: '',
    pending: null,
    init() {
        this.$el.addEventListener('input', () => {
            clearTimeout(this.pending);
            this.pending = setTimeout(() => this.save(), 1500);
        });
    },
    async save() {
        const form = this.$el.closest('form') || this.$el;
        const data = new FormData(form);
        const answer = {};
        for (const [key, value] of data.entries()) {
            const m = key.match(/^answer\[([^\]]+)\](?:\[(\d*)\])?$/);
            if (!m || value instanceof File) continue;
            if (m[2] !== undefined) {
                answer[m[1]] = answer[m[1]] || [];
                m[2] === '' ? answer[m[1]].push(value) : (answer[m[1]][Number(m[2])] = value);
            } else {
                answer[m[1]] = value;
            }
        }
        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                body: JSON.stringify({ answer }),
            });
            this.status = res.ok ? 'Draft saved' : 'Draft not saved';
        } catch {
            this.status = 'Offline — draft not saved';
        }
    },
}));

/** Merge objects keeping getters intact (object spread would freeze them). */
window.mixin = (...objects) => objects.reduce((target, o) => Object.defineProperties(target, Object.getOwnPropertyDescriptors(o)), {});

/**
 * Live price preview for requesters. Mirrors the server's integer-cent maths;
 * the server recalculates everything on submit.
 */
window.priceCalc = (commission, reward = '0.50', slots = 10) => ({
    reward: reward,
    slots: slots,
    toCents(v) {
        const m = String(v ?? '').trim().match(/^(\d*)(?:\.(\d*))?$/);
        if (!m) return 0;
        const frac = ((m[2] || '') + '000').slice(0, 3);
        return Number(m[1] || 0) * 100 + Number(frac.slice(0, 2)) + (Number(frac[2]) >= 5 ? 1 : 0);
    },
    fmt(c) { return '$' + (Math.floor(c / 100)).toLocaleString('en-US') + '.' + String(c % 100).padStart(2, '0'); },
    get bp() { return this.toCents(commission); },
    get rewardCents() { return this.toCents(this.reward); },
    get feeCents() { return Math.floor((this.rewardCents * this.bp + 5000) / 10000); },
    get unitCents() { return this.rewardCents + this.feeCents; },
    get totalCents() { return this.unitCents * Math.max(1, parseInt(this.slots || 1, 10)); },
});
Alpine.data('priceCalc', window.priceCalc);

/** Lazy-loaded Chart.js wrapper: <canvas x-data="chart(config)"> */
Alpine.data('chart', (config) => ({
    async init() {
        const { default: Chart } = await import('chart.js/auto');
        Chart.defaults.color = '#8B95A5';
        Chart.defaults.borderColor = '#232A35';
        Chart.defaults.font.family = 'Inter, ui-sans-serif, system-ui, sans-serif';
        Chart.defaults.font.size = 11;
        Chart.defaults.plugins.tooltip.backgroundColor = '#1D232D';
        Chart.defaults.plugins.tooltip.borderColor = '#2F3845';
        Chart.defaults.plugins.tooltip.borderWidth = 1;
        Chart.defaults.plugins.tooltip.titleColor = '#F5F7FA';
        Chart.defaults.plugins.tooltip.bodyColor = '#C7CEDA';
        Chart.defaults.plugins.tooltip.padding = 10;
        Chart.defaults.plugins.legend.labels.boxWidth = 10;
        Chart.defaults.plugins.legend.labels.boxHeight = 10;
        new Chart(this.$el, config);
    },
}));

window.Alpine = Alpine;
Alpine.start();
