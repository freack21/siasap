import {
    Chart,
    LineController,
    LineElement,
    PointElement,
    LinearScale,
    CategoryScale,
    Filler,
    Tooltip,
    Legend,
} from 'chart.js';

Chart.register(LineController, LineElement, PointElement, LinearScale, CategoryScale, Filler, Tooltip, Legend);

Chart.defaults.font.family = "'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif";
Chart.defaults.font.size = 11;
Chart.defaults.color = '#64748b';
Chart.defaults.locale = 'id-ID';

/**
 * Komponen Alpine untuk grafik garis.
 *
 * Data dibaca dari atribut `data-chart` (JSON) pada elemen x-ref="source".
 * Karena atribut tersebut berada di luar `wire:ignore`, Livewire memperbarui
 * nilainya setiap polling; MutationObserver lalu memperbarui grafik tanpa
 * membuat ulang canvas.
 *
 * Format data: { labels: [...], datasets: [{ label, data: [...], color }], yTitle?: string }
 */
function lineChart() {
    return {
        chart: null,
        observer: null,

        init() {
            this.render(this.read());

            this.observer = new MutationObserver(() => this.update(this.read()));
            this.observer.observe(this.$refs.source, { attributes: true, attributeFilter: ['data-chart'] });
        },

        destroy() {
            this.observer?.disconnect();
            this.chart?.destroy();
        },

        read() {
            try {
                return JSON.parse(this.$refs.source.dataset.chart || '{}');
            } catch {
                return { labels: [], datasets: [] };
            }
        },

        datasets(payload) {
            return (payload.datasets || []).map((set) => {
                // Titik ditampilkan bila datanya sedikit, agar satu-dua pembacaan tetap terlihat.
                const filled = (set.data || []).filter((value) => value !== null).length;

                return {
                    label: set.label,
                    data: set.data,
                    borderColor: set.color,
                    backgroundColor: set.color + '1a',
                    fill: payload.datasets.length === 1,
                    tension: 0.35,
                    borderWidth: 2,
                    pointRadius: filled <= 3 ? 3 : 0,
                    pointHoverRadius: 4,
                    spanGaps: true,
                };
            });
        },

        render(payload) {
            this.chart = new Chart(this.$refs.canvas, {
                type: 'line',
                data: { labels: payload.labels || [], datasets: this.datasets(payload) },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: {
                            display: (payload.datasets || []).length > 1,
                            position: 'bottom',
                            labels: { usePointStyle: true, pointStyle: 'circle', boxWidth: 6, padding: 16 },
                        },
                        tooltip: {
                            backgroundColor: '#0f172a',
                            padding: 10,
                            cornerRadius: 8,
                            titleFont: { weight: '600' },
                        },
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { maxTicksLimit: 8, maxRotation: 0 },
                        },
                        y: {
                            beginAtZero: true,
                            grid: { color: '#f1f5f9' },
                            border: { display: false },
                            title: { display: !!payload.yTitle, text: payload.yTitle || '' },
                        },
                    },
                },
            });
        },

        update(payload) {
            if (!this.chart) {
                return;
            }

            this.chart.data.labels = payload.labels || [];
            this.chart.data.datasets = this.datasets(payload);
            this.chart.options.scales.y.title.display = !!payload.yTitle;
            this.chart.options.scales.y.title.text = payload.yTitle || '';
            this.chart.update('none');
        },
    };
}

document.addEventListener('alpine:init', () => {
    window.Alpine.data('lineChart', lineChart);
});
