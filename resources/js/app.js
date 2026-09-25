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

// Watch & earn: agent status + rewarded video ads.
// cfg = { provider, adUnitPath, minSeconds, state, urls: { status, start, complete } }
Alpine.data('watchEarn', (cfg) => ({
    state: cfg.state,
    phase: 'idle', // idle | loading | playing | verifying | done | error
    message: '',
    progress: 0,
    finishedNotice: false,
    view: null,
    timer: null,

    init() {
        this.wasWorking = this.state.agent.working;
        this.poll = setInterval(() => this.refresh(), 5000);
        document.addEventListener('visibilitychange', () => document.visibilityState === 'visible' && this.refresh());
    },
    destroy() { clearInterval(this.poll); clearInterval(this.timer); },

    get canWatch() { return this.state.enabled && this.state.today < this.state.cap && ['idle', 'done', 'error'].includes(this.phase); },

    async request(url, method = 'GET') {
        const res = await fetch(url, { method, headers: { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content } });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) throw new Error(data.message || 'Something went wrong. Please try again.');
        return data;
    },

    async refresh() {
        try {
            this.state = await this.request(cfg.urls.status);
        } catch { return; }
        if (this.wasWorking && !this.state.agent.working) this.agentFinished();
        this.wasWorking = this.state.agent.working;
        document.title = (this.state.agent.working ? '● Claude is working — ' : '✓ Claude is done — ') + 'Watch & earn';
    },

    agentFinished() {
        this.finishedNotice = true;
        if ('Notification' in window && Notification.permission === 'granted') {
            new Notification('Claude finished', { body: 'Your agent is done — switch back to your terminal.' });
        }
    },

    askNotify() { 'Notification' in window && Notification.requestPermission(); },

    async watch() {
        if (!this.canWatch) return;
        this.phase = 'loading';
        this.message = '';
        try {
            this.view = (await this.request(cfg.urls.start, 'POST')).view;
            cfg.provider === 'google_rewarded' ? this.playGoogle() : this.playDemo();
        } catch (e) { this.fail(e.message); }
    },

    // Built-in placeholder so the whole flow works without an ad account.
    playDemo() {
        this.phase = 'playing';
        const total = (cfg.minSeconds + 1) * 1000, started = Date.now();
        this.timer = setInterval(() => {
            this.progress = Math.min(100, ((Date.now() - started) / total) * 100);
            if (this.progress >= 100) { clearInterval(this.timer); this.granted(); }
        }, 200);
    },

    // Google Ad Manager rewarded web ad (GPT out-of-page REWARDED format).
    playGoogle() {
        if (!cfg.adUnitPath) return this.fail('Ads are not configured yet (GOOGLE_AD_UNIT_PATH is empty).');
        window.googletag = window.googletag || { cmd: [] };
        if (!document.getElementById('gpt-js')) {
            const s = document.createElement('script');
            s.id = 'gpt-js'; s.async = true; s.src = 'https://securepubads.g.doubleclick.net/tag/js/gpt.js';
            s.onerror = () => this.fail('The ad could not load. Disable your ad blocker for this site and try again.');
            document.head.appendChild(s);
        }
        const gt = window.googletag;
        gt.cmd.push(() => {
            const slot = gt.defineOutOfPageSlot(cfg.adUnitPath, gt.enums.OutOfPageFormat.REWARDED);
            if (!slot) return this.fail('Rewarded ads are not supported in this browser or window size.');
            slot.addService(gt.pubads());
            const pubads = gt.pubads();
            const onReady = (e) => { this.phase = 'playing'; e.makeRewardedVisible(); };
            const onGranted = () => this.granted();
            const onClosed = () => { gt.destroySlots([slot]); cleanup(); if (this.phase === 'playing') this.fail('The ad was closed before the reward was earned.'); };
            const onEmpty = (e) => { if (e.slot === slot && e.isEmpty) { gt.destroySlots([slot]); cleanup(); this.fail('No ad is available right now. Try again in a minute.'); } };
            const cleanup = () => {
                pubads.removeEventListener('rewardedSlotReady', onReady);
                pubads.removeEventListener('rewardedSlotGranted', onGranted);
                pubads.removeEventListener('rewardedSlotClosed', onClosed);
                pubads.removeEventListener('slotRenderEnded', onEmpty);
            };
            pubads.addEventListener('rewardedSlotReady', onReady);
            pubads.addEventListener('rewardedSlotGranted', onGranted);
            pubads.addEventListener('rewardedSlotClosed', onClosed);
            pubads.addEventListener('slotRenderEnded', onEmpty);
            gt.enableServices();
            gt.display(slot);
        });
    },

    async granted() {
        this.phase = 'verifying';
        try {
            const data = await this.request(cfg.urls.complete.replace('__VIEW__', this.view), 'POST');
            this.state = data.state;
            this.message = data.message;
            this.phase = 'done';
        } catch (e) { this.fail(e.message); }
        this.progress = 0;
    },

    fail(message) { clearInterval(this.timer); this.progress = 0; this.phase = 'error'; this.message = message; },
}));

window.Alpine = Alpine;
Alpine.start();
