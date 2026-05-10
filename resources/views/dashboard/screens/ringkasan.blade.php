<div class="summary-screen">
<!-- Metrics -->
<div class="metrics-grid">
    <div class="metric-card temperature">
        <div class="metric-header">
            <div class="metric-label">Suhu</div>
            <div class="metric-icon">
                <i class="bi bi-thermometer-half"></i>
            </div>
        </div>
        <div class="metric-value">
            <span id="temperatureValue">{{ $latest ? number_format($latest->suhu, 1) : '--' }}</span>
            <span class="metric-unit">&deg;C</span>
        </div>
        <p class="metric-note" id="latestTemperatureTime">
            {{ $latest?->created_at?->timezone(config('app.timezone'))->format('d M Y H:i:s') ?? 'Belum ada data sensor' }}
        </p>
    </div>

    <div class="metric-card humidity">
        <div class="metric-header">
            <div class="metric-label">Kelembaban</div>
            <div class="metric-icon">
                <i class="bi bi-droplet-half"></i>
            </div>
        </div>
        <div class="metric-value">
            <span id="humidityValue">{{ $latest ? number_format($latest->kelembaban, 1) : '--' }}</span>
            <span class="metric-unit">%</span>
        </div>
        <p class="metric-note" id="latestHumidityTime">
            {{ $latest?->created_at?->timezone(config('app.timezone'))->format('d M Y H:i:s') ?? 'Menunggu NodeMCU' }}
        </p>
    </div>

    <div class="metric-card status">
        <div class="metric-header">
            <div class="metric-label">Status API</div>
            <div class="metric-icon">
                <i class="bi bi-router"></i>
            </div>
        </div>
        <div class="metric-value compact" id="apiStatus">Siap</div>
        <p class="metric-note" id="lastRefresh">Data akan diperbarui otomatis</p>
    </div>

    <div class="metric-card pump">
        <div class="metric-header">
            <div class="metric-label">Pompa</div>
            <div class="metric-icon">
                <i class="bi bi-power"></i>
            </div>
        </div>
        <div class="metric-value compact" id="pumpMode">
            {{ $pumpStatus['effective_active'] ? $pumpStatus['source'] : 'OFF' }}
        </div>
        <p class="metric-note" id="pumpNote">
            @if ($pumpStatus['manual_active'])
                Tombol manual aktif
            @elseif ($pumpStatus['automatic_active'])
                Jadwal aktif: {{ $pumpStatus['active_schedule']['name'] }}
            @else
                Menunggu jadwal/manual
            @endif
        </p>
    </div>
</div>

<!-- Chart -->
<section style="margin-top: 24px;">
    <!-- Header -->
    <div style="display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:16px; flex-wrap:wrap; gap:12px;">
        <div>
            <div class="section-label" id="sensorRangeLabel">25 Menit Terakhir</div>
            <h2 class="section-title">Grafik Sensor</h2>
        </div>
        <!-- Range buttons -->
        <div id="rangeSwitch" style="display:flex; gap:4px; background:#f5f5f5; padding:4px; border-radius:8px;" aria-label="Rentang grafik">
            <button class="crb" data-range="1m"  style="padding:6px 12px;border:none;background:transparent;border-radius:6px;font-size:13px;font-weight:500;color:#666;cursor:pointer;transition:all .2s;">1 Menit</button>
            <button class="crb" data-range="5m"  style="padding:6px 12px;border:none;background:transparent;border-radius:6px;font-size:13px;font-weight:500;color:#666;cursor:pointer;transition:all .2s;">5 Menit</button>
            <button class="crb active" data-range="25m" style="padding:6px 12px;border:none;background:#fff;border-radius:6px;font-size:13px;font-weight:500;color:#6dab28;cursor:pointer;transition:all .2s;box-shadow:0 1px 3px rgba(0,0,0,.1);">25 Menit</button>
            <button class="crb" data-range="1h"  style="padding:6px 12px;border:none;background:transparent;border-radius:6px;font-size:13px;font-weight:500;color:#666;cursor:pointer;transition:all .2s;">1 Jam</button>
            <button class="crb" data-range="1d"  style="padding:6px 12px;border:none;background:transparent;border-radius:6px;font-size:13px;font-weight:500;color:#666;cursor:pointer;transition:all .2s;">1 Hari</button>
        </div>
    </div>

    <!-- Dataset toggle -->
    <div style="display:flex; gap:8px; margin-bottom:14px;">
        <button class="cdb active" data-dataset="both"
            style="display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border:1.5px solid #e0e0e0;border-radius:8px;background:#fff;font-size:13px;font-weight:600;color:#555;cursor:pointer;transition:all .2s;border-color:#6dab28;color:#6dab28;box-shadow:0 0 0 3px rgba(109,171,40,.1);">
            <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:linear-gradient(135deg,#6dab28,#42a5f5);"></span>
            Keduanya
        </button>
        <button class="cdb" data-dataset="suhu"
            style="display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border:1.5px solid #e0e0e0;border-radius:8px;background:#fff;font-size:13px;font-weight:600;color:#555;cursor:pointer;transition:all .2s;">
            <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#6dab28;"></span>
            Suhu
        </button>
        <button class="cdb" data-dataset="kelembaban"
            style="display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border:1.5px solid #e0e0e0;border-radius:8px;background:#fff;font-size:13px;font-weight:600;color:#555;cursor:pointer;transition:all .2s;">
            <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#42a5f5;"></span>
            Kelembaban
        </button>
    </div>

    <!-- Canvas -->
    <div style="background:#fff;border-radius:12px;padding:20px;box-shadow:0 1px 3px rgba(0,0,0,.08);height:340px;">
        <canvas id="sensorChart" style="max-height:100%;"></canvas>
    </div>
</section>
</div>

<script>
// ── Helpers ──────────────────────────────────────────────
function makeGradient(ctx, chartArea, r, g, b, top, bot) {
    const g2 = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
    g2.addColorStop(0,   `rgba(${r},${g},${b},${top})`);
    g2.addColorStop(0.5, `rgba(${r},${g},${b},${top * 0.38})`);
    g2.addColorStop(1,   `rgba(${r},${g},${b},${bot})`);
    return g2;
}

// ── Plugin: gradient fill ─────────────────────────────────
const gradientPlugin = {
    id: 'gradientBg',
    afterLayout(chart) {
        const { ctx, chartArea } = chart;
        if (!chartArea) return;
        chart.data.datasets[0].backgroundColor = makeGradient(ctx, chartArea, 109, 171, 40,  0.58, 0);
        chart.data.datasets[1].backgroundColor = makeGradient(ctx, chartArea, 66,  165, 245, 0.45, 0);
    }
};

// ── Plugin: crosshair ─────────────────────────────────────
const crosshairPlugin = {
    id: 'crosshair',
    afterDraw(chart) {
        if (!chart.tooltip._active?.length) return;
        const { ctx, scales } = chart;
        const x = chart.tooltip._active[0].element.x;
        ctx.save();
        ctx.beginPath();
        ctx.moveTo(x, scales.y.top);
        ctx.lineTo(x, scales.y.bottom);
        ctx.lineWidth = 1;
        ctx.strokeStyle = 'rgba(0,0,0,0.15)';
        ctx.stroke();
        ctx.restore();
    }
};

// ── Chart config ──────────────────────────────────────────
const sensorChart = new Chart(document.getElementById('sensorChart').getContext('2d'), {
    type: 'line',
    plugins: [gradientPlugin, crosshairPlugin],
    data: {
        labels: [],
        datasets: [{
            label: 'Suhu (°C)',
            data: [],
            borderColor: '#6dab28',
            backgroundColor: 'rgba(109,171,40,0.4)',
            borderWidth: 2.5, fill: true, tension: 0.35,
            pointRadius: 0, pointHitRadius: 20, pointHoverRadius: 7,
            pointHoverBackgroundColor: '#6dab28',
            pointHoverBorderColor: '#fff', pointHoverBorderWidth: 2.5,
        }, {
            label: 'Kelembaban (%)',
            data: [],
            borderColor: '#42a5f5',
            backgroundColor: 'rgba(66,165,245,0.3)',
            borderWidth: 2.5, fill: true, tension: 0.35,
            pointRadius: 0, pointHitRadius: 20, pointHoverRadius: 7,
            pointHoverBackgroundColor: '#42a5f5',
            pointHoverBorderColor: '#fff', pointHoverBorderWidth: 2.5,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#fff', titleColor: '#222', bodyColor: '#555',
                borderColor: 'rgba(0,0,0,.1)', borderWidth: 1,
                padding: { x: 14, y: 10 }, cornerRadius: 8,
                displayColors: true, usePointStyle: true,
                callbacks: {
                    label(ctx) {
                        let l = ctx.dataset.label + ': ' + ctx.parsed.y.toFixed(1);
                        l += ctx.datasetIndex === 0 ? ' °C' : ' %';
                        return l;
                    }
                }
            }
        },
        scales: {
            x: {
                border: { display: false }, grid: { display: false },
                ticks: { color: '#aaa', font: { size: 11, family: "'Inter',sans-serif" }, maxRotation: 0, autoSkipPadding: 24, padding: 6 }
            },
            y: {
                beginAtZero: false, border: { display: false },
                grid: { display: true, color: 'rgba(0,0,0,.06)', lineWidth: 1 },
                ticks: { color: '#aaa', font: { size: 11, family: "'Inter',sans-serif" }, padding: 12, maxTicksLimit: 5 }
            }
        },
        layout: { padding: { top: 8, right: 8, bottom: 0, left: 0 } }
    }
});

// ── State ─────────────────────────────────────────────────
let activeDataset = 'both'; // 'both' | 'suhu' | 'kelembaban'

// ── Dataset visibility ────────────────────────────────────
function applyDatasetVisibility() {
    sensorChart.data.datasets[0].hidden = (activeDataset === 'kelembaban');
    sensorChart.data.datasets[1].hidden = (activeDataset === 'suhu');
    sensorChart.update('none');
}

// ── Dataset toggle buttons ────────────────────────────────
const datasetColors = { both: '#6dab28', suhu: '#6dab28', kelembaban: '#42a5f5' };

function refreshDatasetButtons() {
    document.querySelectorAll('.cdb').forEach(btn => {
        const ds = btn.dataset.dataset;
        const isActive = ds === activeDataset;
        const color = datasetColors[ds];
        btn.style.borderColor   = isActive ? color : '#e0e0e0';
        btn.style.color         = isActive ? color : '#555';
        btn.style.boxShadow     = isActive ? `0 0 0 3px ${color}1a` : 'none';
        btn.style.background    = '#fff';
    });
}

document.querySelectorAll('.cdb').forEach(btn => {
    btn.addEventListener('click', function () {
        activeDataset = this.dataset.dataset;
        refreshDatasetButtons();
        applyDatasetVisibility();
    });
    btn.addEventListener('mouseenter', function () {
        if (this.dataset.dataset !== activeDataset) this.style.borderColor = '#bbb';
    });
    btn.addEventListener('mouseleave', function () {
        if (this.dataset.dataset !== activeDataset) this.style.borderColor = '#e0e0e0';
    });
});

// ── Range labels ──────────────────────────────────────────
const rangeLabels = { '1m':'1 Menit Terakhir','5m':'5 Menit Terakhir','25m':'25 Menit Terakhir','1h':'1 Jam Terakhir','1d':'1 Hari Terakhir' };

function refreshRangeButtons() {
    document.querySelectorAll('.crb').forEach(btn => {
        const isActive = btn.classList.contains('active');
        btn.style.background = isActive ? '#fff' : 'transparent';
        btn.style.color      = isActive ? '#6dab28' : '#666';
        btn.style.boxShadow  = isActive ? '0 1px 3px rgba(0,0,0,.1)' : 'none';
    });
}

// ── Load data ─────────────────────────────────────────────
async function loadSensorData(range = '25m') {
    try {
        const res  = await fetch(`/dashboard/data?sensor_range=${range}`);
        const data = await res.json();
        sensorChart.data.labels            = data.readings.map(r => r.label);
        sensorChart.data.datasets[0].data  = data.readings.map(r => r.suhu);
        sensorChart.data.datasets[1].data  = data.readings.map(r => r.kelembaban);
        applyDatasetVisibility();
    } catch (e) { console.error('Error:', e); }
}

// ── Range buttons ─────────────────────────────────────────
document.querySelectorAll('.crb').forEach(btn => {
    btn.addEventListener('click', function () {
        document.querySelectorAll('.crb').forEach(b => { b.classList.remove('active'); b.setAttribute('aria-pressed','false'); });
        this.classList.add('active');
        this.setAttribute('aria-pressed','true');
        refreshRangeButtons();
        document.getElementById('sensorRangeLabel').textContent = rangeLabels[this.dataset.range];
        loadSensorData(this.dataset.range);
    });
    btn.addEventListener('mouseenter', function () { if (!this.classList.contains('active')) this.style.background = '#e8e8e8'; });
    btn.addEventListener('mouseleave', function () { if (!this.classList.contains('active')) this.style.background = 'transparent'; });
});

// ── Auto-refresh & init ───────────────────────────────────
setInterval(() => {
    const r = document.querySelector('.crb.active')?.dataset.range ?? '25m';
    loadSensorData(r);
}, 5000);

loadSensorData('25m');
</script>