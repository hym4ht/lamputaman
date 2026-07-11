<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Garden Monitoring - Live Monitoring</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    @vite(['resources/css/app.css', 'resources/css/dashboard.css', 'resources/js/app.js'])
    
    <style>
        /* Custom styling to align public layout beautifully */
        .public-layout {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1.25rem;
        }
        .metric-card {
            min-height: auto;
            padding: 1.25rem;
            position: relative;
            overflow: hidden;
        }
        .metric-value {
            font-size: 2.25rem;
            font-weight: 800;
        }
        .metric-value.compact {
            font-size: 1.35rem;
            display: flex;
            align-items: center;
            height: 36px;
        }
        .chart-range-button.active {
            background-color: #6dab28 !important;
            color: #ffffff !important;
        }
        .cdb.active {
            border-color: #6dab28 !important;
            color: #6dab28 !important;
            box-shadow: 0 0 0 3px rgba(109, 171, 40, 0.15) !important;
        }
    </style>
</head>
<body class="dashboard-page min-h-screen pb-16">

    <x-navbar />

    <div class="public-layout">
        
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
            <div>
                <div class="page-label text-xs font-bold tracking-wider text-gray-400 uppercase mb-1">Live Monitoring</div>
                <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Kondisi Realtime Taman</h1>
                <p class="text-sm text-gray-500 mt-1">Pantau kelembaban, suhu, dan status aktif perangkat secara live.</p>
            </div>
            <div class="flex items-center gap-2 bg-emerald-50 border border-emerald-100/80 px-3.5 py-1.5 rounded-full text-emerald-700 text-xs font-bold shadow-sm">
                <span class="relative flex h-2.5 w-2.5">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
                </span>
                <span>Pembaruan Otomatis</span>
            </div>
        </div>

        <div class="metrics-grid mb-8">
            <div class="metric-card temperature">
                <div class="metric-header">
                    <div class="metric-label">Suhu Lingkungan</div>
                    <div class="metric-icon">
                        <i class="bi bi-thermometer-half"></i>
                    </div>
                </div>
                <div class="metric-value">
                    <span id="live-suhu">--</span>
                    <span class="metric-unit">&deg;C</span>
                </div>
                <p class="metric-note text-xs text-gray-400 mt-1" id="latest-suhu-time">Menghubungkan ke NodeMCU...</p>
            </div>

            <div class="metric-card humidity">
                <div class="metric-header">
                    <div class="metric-label">Kelembaban Udara</div>
                    <div class="metric-icon">
                        <i class="bi bi-droplet-half"></i>
                    </div>
                </div>
                <div class="metric-value">
                    <span id="live-kelembaban">--</span>
                    <span class="metric-unit">%</span>
                </div>
                <p class="metric-note text-xs text-gray-400 mt-1" id="latest-kelembaban-time">Menghubungkan ke NodeMCU...</p>
            </div>

            <div class="metric-card pump">
                <div class="metric-header">
                    <div class="metric-label">Status Pompa</div>
                    <div class="metric-icon">
                        <i class="bi bi-power"></i>
                    </div>
                </div>
                <div class="metric-value compact text-gray-500" id="pumpMetricValue">--</div>
                <p class="metric-note text-xs text-gray-400 mt-1" id="pumpMetricNote">Memuat status pompa...</p>
            </div>

            <div class="metric-card lamp" style="min-height: auto;">
                <div class="metric-header">
                    <div class="metric-label">Status Lampu</div>
                    <div class="metric-icon">
                        <i class="bi bi-lightbulb"></i>
                    </div>
                </div>
                <div class="metric-value compact text-gray-500 mb-1" id="lampMetricValue">--</div>
                
                <div class="flex gap-1.5 mb-2.5" id="lampBadgesContainer">
                    <span id="lampBadge-lampu1" class="px-2 py-0.5 rounded text-[10px] font-bold bg-gray-100 text-gray-400 border border-gray-200/50">L1</span>
                    <span id="lampBadge-lampu2" class="px-2 py-0.5 rounded text-[10px] font-bold bg-gray-100 text-gray-400 border border-gray-200/50">L2</span>
                    <span id="lampBadge-lampu3" class="px-2 py-0.5 rounded text-[10px] font-bold bg-gray-100 text-gray-400 border border-gray-200/50">L3</span>
                </div>

                <p class="metric-note text-xs text-gray-400" id="lampMetricNote">Memuat status lampu...</p>
            </div>

            <div class="metric-card status">
                <div class="metric-header">
                    <div class="metric-label">Status Koneksi</div>
                    <div class="metric-icon">
                        <i class="bi bi-router"></i>
                    </div>
                </div>
                <div class="metric-value compact text-yellow-600" id="apiStatus">Memuat</div>
                <p class="metric-note text-xs text-gray-400 mt-1" id="lastRefresh">Memuat data terbaru...</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2">
                <div class="chart-section h-full bg-white border border-[#e8e8e8] rounded-xl p-5 shadow-sm">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                        <div>
                            <div class="section-label text-xs font-semibold text-gray-400 uppercase tracking-wider" id="sensorRangeLabel">25 Menit Terakhir</div>
                            <h2 class="section-title text-xl font-bold text-gray-800">Grafik Tren Sensor</h2>
                        </div>
                        
                        <div class="chart-range-switch flex gap-1 p-1 bg-gray-50 border border-gray-200/80 rounded-lg" id="rangeSwitch" aria-label="Rentang grafik">
                            <button class="chart-range-button crb px-3 py-1.5 text-xs font-semibold rounded-md transition-all text-gray-600 hover:text-gray-900" data-range="1m">1m</button>
                            <button class="chart-range-button crb px-3 py-1.5 text-xs font-semibold rounded-md transition-all text-gray-600 hover:text-gray-900" data-range="5m">5m</button>
                            <button class="chart-range-button crb active px-3 py-1.5 text-xs font-semibold rounded-md transition-all text-gray-600 hover:text-gray-900" data-range="25m">25m</button>
                            <button class="chart-range-button crb px-3 py-1.5 text-xs font-semibold rounded-md transition-all text-gray-600 hover:text-gray-900" data-range="1h">1h</button>
                            <button class="chart-range-button crb px-3 py-1.5 text-xs font-semibold rounded-md transition-all text-gray-600 hover:text-gray-900" data-range="1d">1d</button>
                        </div>
                    </div>

                    <div class="flex gap-2 mb-4">
                        <button class="cdb active" data-dataset="both"
                            style="display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border:1.5px solid #e0e0e0;border-radius:8px;background:#fff;font-size:13px;font-weight:600;color:#555;cursor:pointer;transition:all .2s;">
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

                    <div class="chart-container" style="height: 340px; position: relative; width: 100%;">
                        <canvas id="sensorChart" style="max-height: 100%;"></canvas>
                    </div>
                </div>
            </div>

            <div>
                <div class="chart-section h-full bg-white border border-[#e8e8e8] rounded-xl p-5 shadow-sm">
                    <div class="section-header mb-6">
                        <div>
                            <div class="section-label text-xs font-semibold text-gray-400 uppercase tracking-wider">Aktif / Nonaktif</div>
                            <h2 class="section-title text-xl font-bold text-gray-800">Status Perangkat</h2>
                        </div>
                    </div>

                    <div class="control-list">
                        <div class="control-item p-3.5 mb-2.5 rounded-xl border border-gray-100 bg-gray-50/50 flex justify-between items-center transition-all duration-200 hover:bg-white hover:border-emerald-200/50 hover:shadow-sm">
                            <div class="flex items-center gap-3">
                                <div class="device-icon-container text-xl flex items-center justify-center w-10 h-10 rounded-lg bg-gray-100 border border-gray-200/60 text-gray-400" id="icon-container-lampu1">
                                    <i class="bi bi-lightbulb" id="icon-lampu1"></i>
                                </div>
                                <div>
                                    <div class="font-bold text-gray-800 text-sm">Lampu Taman 1</div>
                                    <div class="text-[11px] text-gray-500 font-semibold" id="status-desc-lampu1">Memuat status...</div>
                                </div>
                            </div>
                            <div>
                                <span id="badge-lampu1" class="badge bg-secondary px-2.5 py-1 text-[10px] rounded-full">MEMUAT...</span>
                            </div>
                        </div>

                        <div class="control-item p-3.5 mb-2.5 rounded-xl border border-gray-100 bg-gray-50/50 flex justify-between items-center transition-all duration-200 hover:bg-white hover:border-emerald-200/50 hover:shadow-sm">
                            <div class="flex items-center gap-3">
                                <div class="device-icon-container text-xl flex items-center justify-center w-10 h-10 rounded-lg bg-gray-100 border border-gray-200/60 text-gray-400" id="icon-container-lampu2">
                                    <i class="bi bi-lightbulb" id="icon-lampu2"></i>
                                </div>
                                <div>
                                    <div class="font-bold text-gray-800 text-sm">Lampu Taman 2</div>
                                    <div class="text-[11px] text-gray-500 font-semibold" id="status-desc-lampu2">Memuat status...</div>
                                </div>
                            </div>
                            <div>
                                <span id="badge-lampu2" class="badge bg-secondary px-2.5 py-1 text-[10px] rounded-full">MEMUAT...</span>
                            </div>
                        </div>

                        <div class="control-item p-3.5 mb-2.5 rounded-xl border border-gray-100 bg-gray-50/50 flex justify-between items-center transition-all duration-200 hover:bg-white hover:border-emerald-200/50 hover:shadow-sm">
                            <div class="flex items-center gap-3">
                                <div class="device-icon-container text-xl flex items-center justify-center w-10 h-10 rounded-lg bg-gray-100 border border-gray-200/60 text-gray-400" id="icon-container-lampu3">
                                    <i class="bi bi-lightbulb" id="icon-lampu3"></i>
                                </div>
                                <div>
                                    <div class="font-bold text-gray-800 text-sm">Lampu Taman 3</div>
                                    <div class="text-[11px] text-gray-500 font-semibold" id="status-desc-lampu3">Memuat status...</div>
                                </div>
                            </div>
                            <div>
                                <span id="badge-lampu3" class="badge bg-secondary px-2.5 py-1 text-[10px] rounded-full">MEMUAT...</span>
                            </div>
                        </div>

                        <div class="control-item p-3.5 rounded-xl border border-gray-100 bg-gray-50/50 flex justify-between items-center transition-all duration-200 hover:bg-white hover:border-emerald-200/50 hover:shadow-sm">
                            <div class="flex items-center gap-3">
                                <div class="device-icon-container text-xl flex items-center justify-center w-10 h-10 rounded-lg bg-gray-100 border border-gray-200/60 text-gray-400" id="icon-container-pompa">
                                    <i class="bi bi-droplet" id="icon-pompa"></i>
                                </div>
                                <div>
                                    <div class="font-bold text-gray-800 text-sm">Pompa Penyiraman</div>
                                    <div class="text-[11px] text-gray-500 font-semibold" id="status-desc-pompa">Memuat status...</div>
                                </div>
                            </div>
                            <div>
                                <span id="badge-pompa" class="badge bg-secondary px-2.5 py-1 text-[10px] rounded-full">MEMUAT...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

<x-footer />
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
                ctx.strokeStyle = 'rgba(0,0,0,0.11)';
                ctx.stroke();
                ctx.restore();
            }
        };

        // ── Chart config ──────────────────────────────────────────
        const ctx = document.getElementById('sensorChart').getContext('2d');
        const sensorChart = new Chart(ctx, {
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
        let activeRange = '25m';
        let activeDataset = 'both'; // 'both' | 'suhu' | 'kelembaban'
        const rangeLabels = { 
            '1m': '1 Menit Terakhir', 
            '5m': '5 Menit Terakhir', 
            '25m': '25 Menit Terakhir', 
            '1h': '1 Jam Terakhir', 
            '1d': '1 Hari Terakhir' 
        };

        // ── Dataset Visibility ────────────────────────────────────
        function applyDatasetVisibility() {
            sensorChart.data.datasets[0].hidden = (activeDataset === 'kelembaban');
            sensorChart.data.datasets[1].hidden = (activeDataset === 'suhu');
            sensorChart.update('none');
        }

        // ── Dataset Toggle Buttons Interaction ────────────────────
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

        // ── Device item layout updates ────────────────────────────
        function updateDeviceItem(device, controls, manualControls, pumpStatus, lampStatus) {
            const isActive = !!controls[device];
            const isManual = !!manualControls[device];
            
            const badge = document.getElementById(`badge-${device}`);
            const iconContainer = document.getElementById(`icon-container-${device}`);
            const icon = document.getElementById(`icon-${device}`);
            const desc = document.getElementById(`status-desc-${device}`);
            
            if (!badge || !iconContainer || !icon || !desc) return;

            const isLamp = device.startsWith('lampu');
            
            if (isActive) {
                badge.textContent = "AKTIF";
                badge.className = "badge bg-success px-2.5 py-1 text-[10px] rounded-full";
                iconContainer.className = "device-icon-container text-xl flex items-center justify-center w-10 h-10 rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-100 shadow-sm";
                
                if (isLamp) {
                    icon.className = "bi bi-lightbulb-fill animate-pulse";
                    const isScheduledLamp = !!(lampStatus.active_devices && lampStatus.active_devices[device]);
                    if (isScheduledLamp && isManual) {
                        desc.textContent = "Aktif (Manual + Jadwal)";
                    } else if (isScheduledLamp) {
                        desc.textContent = "Aktif (Jadwal)";
                    } else {
                        desc.textContent = "Aktif (Manual)";
                    }
                } else {
                    icon.className = "bi bi-droplet-fill text-blue-500 animate-bounce";
                    desc.textContent = `Penyiraman aktif (${pumpStatus.source})`;
                }
            } else {
                badge.textContent = "MATI";
                badge.className = "badge bg-secondary px-2.5 py-1 text-[10px] rounded-full bg-gray-200 text-gray-500";
                iconContainer.className = "device-icon-container text-xl flex items-center justify-center w-10 h-10 rounded-lg bg-gray-100 border border-gray-200/60 text-gray-400";
                
                if (isLamp) {
                    icon.className = "bi bi-lightbulb";
                } else {
                    icon.className = "bi bi-droplet";
                }
                desc.textContent = "Nonaktif";
            }
        }

        // ── AJAX Polling ──────────────────────────────────────────
        async function fetchMonitoringData() {
            try {
                const res = await fetch(`{{ route('public.dashboard.data', [], false) }}?sensor_range=${activeRange}`);
                const data = await res.json();

                // 1. Update Metrics Cards (Suhu & Kelembaban)
                if (data.latest) {
                    document.getElementById('live-suhu').textContent = data.latest.suhu !== null ? Number(data.latest.suhu).toFixed(1) : '--';
                    document.getElementById('live-kelembaban').textContent = data.latest.kelembaban !== null ? Number(data.latest.kelembaban).toFixed(1) : '--';
                    
                    const formattedTime = data.latest.label ?? 'Belum ada data';
                    document.getElementById('latest-suhu-time').textContent = "Terakhir: " + formattedTime;
                    document.getElementById('latest-kelembaban-time').textContent = "Terakhir: " + formattedTime;
                }

                // 2. Update Device Status List & Pompa Card
                if (data.controls && data.manual_controls && data.pump && data.lamp) {
                    updateDeviceItem('lampu1', data.controls, data.manual_controls, data.pump, data.lamp);
                    updateDeviceItem('lampu2', data.controls, data.manual_controls, data.pump, data.lamp);
                    updateDeviceItem('lampu3', data.controls, data.manual_controls, data.pump, data.lamp);
                    updateDeviceItem('pompa', data.controls, data.manual_controls, data.pump, data.lamp);

                    // Update Pump Metric Card
                    const pumpMetricValue = document.getElementById('pumpMetricValue');
                    const pumpMetricNote = document.getElementById('pumpMetricNote');
                    
                    if (data.pump.effective_active) {
                        pumpMetricValue.textContent = data.pump.source;
                        pumpMetricValue.className = "metric-value compact text-success font-bold text-base";
                    } else {
                        pumpMetricValue.textContent = "OFF";
                        pumpMetricValue.className = "metric-value compact text-gray-500 font-bold text-base";
                    }

                    if (data.pump.manual_active) {
                        pumpMetricNote.textContent = "Tombol manual aktif";
                    } else if (data.pump.automatic_active) {
                        pumpMetricNote.textContent = `Jadwal aktif: ${data.pump.active_schedule?.name ?? 'Jadwal'}`;
                    } else {
                        pumpMetricNote.textContent = "Menunggu jadwal/manual";
                    }

                    // Update Lamp Metric Card
                    const lampMetricValue = document.getElementById('lampMetricValue');
                    const lampMetricNote = document.getElementById('lampMetricNote');
                    
                    if (lampMetricValue && lampMetricNote) {
                        const activeLampsCount = (data.controls['lampu1'] ? 1 : 0) + 
                                                 (data.controls['lampu2'] ? 1 : 0) + 
                                                 (data.controls['lampu3'] ? 1 : 0);
                        
                        if (activeLampsCount > 0) {
                            if (activeLampsCount === 3) {
                                lampMetricValue.textContent = "SEMUA AKTIF";
                            } else {
                                lampMetricValue.textContent = `${activeLampsCount} AKTIF`;
                            }
                            lampMetricValue.className = "metric-value compact text-success font-bold text-base mb-1";
                        } else {
                            lampMetricValue.textContent = "OFF";
                            lampMetricValue.className = "metric-value compact text-gray-500 font-bold text-base mb-1";
                        }

                        // Update inline badges
                        const lampNames = ['lampu1', 'lampu2', 'lampu3'];
                        lampNames.forEach(device => {
                            const badgeEl = document.getElementById(`lampBadge-${device}`);
                            if (badgeEl) {
                                const isOn = !!data.controls[device];
                                if (isOn) {
                                    badgeEl.className = "px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200/50";
                                } else {
                                    badgeEl.className = "px-2 py-0.5 rounded text-[10px] font-bold bg-gray-100 text-gray-400 border border-gray-200/50";
                                }
                            }
                        });

                        const hasManualLamp = data.manual_controls['lampu1'] || data.manual_controls['lampu2'] || data.manual_controls['lampu3'];
                        if (hasManualLamp && data.lamp.automatic_active) {
                            lampMetricNote.textContent = "Manual + Jadwal aktif";
                        } else if (hasManualLamp) {
                            lampMetricNote.textContent = "Tombol manual aktif";
                        } else if (data.lamp.automatic_active) {
                            const scheduleNames = data.lamp.active_schedules.map(s => s.name).join(', ');
                            lampMetricNote.textContent = `Jadwal aktif: ${scheduleNames || 'Jadwal'}`;
                        } else {
                            lampMetricNote.textContent = "Menunggu jadwal/manual";
                        }
                    }
                }

                // 3. Update Chart readings
                if (data.readings) {
                    sensorChart.data.labels = data.readings.map(r => r.label);
                    sensorChart.data.datasets[0].data = data.readings.map(r => r.suhu);
                    sensorChart.data.datasets[1].data = data.readings.map(r => r.kelembaban);
                    applyDatasetVisibility();
                }

                // Update API Connection Badge
                const apiStatus = document.getElementById('apiStatus');
                if (data.device_connected) {
                    apiStatus.textContent = "TERHUBUNG";
                    apiStatus.className = "metric-value compact text-emerald-600 font-bold text-base";
                } else {
                    apiStatus.textContent = "TERPUTUS";
                    apiStatus.className = "metric-value compact text-rose-600 font-bold text-base";
                }

                if (data.device_last_seen) {
                    document.getElementById('lastRefresh').textContent = "Terakhir aktif: " + data.device_last_seen;
                } else {
                    document.getElementById('lastRefresh').textContent = "Belum ada koneksi dari alat";
                }

            } catch (error) {
                console.error('Gagal memuat data monitoring:', error);
                
                const apiStatus = document.getElementById('apiStatus');
                apiStatus.textContent = "ERROR";
                apiStatus.className = "metric-value compact text-rose-600 font-bold text-base";
                document.getElementById('lastRefresh').textContent = "Koneksi terputus";
            }
        }

        // ── Range Switch Buttons Interaction ──────────────────────
        function refreshRangeButtons() {
            document.querySelectorAll('.crb').forEach(btn => {
                if (btn.dataset.range === activeRange) {
                    btn.classList.add('active');
                } else {
                    btn.classList.remove('active');
                }
            });
        }

        document.querySelectorAll('.crb').forEach(btn => {
            btn.addEventListener('click', function () {
                activeRange = this.dataset.range;
                document.getElementById('sensorRangeLabel').textContent = rangeLabels[activeRange];
                refreshRangeButtons();
                fetchMonitoringData();
            });
        });

        // ── Initial load & intervals ──────────────────────────────
        refreshDatasetButtons();
        refreshRangeButtons();
        fetchMonitoringData();
        
        // Poll data every 5 seconds
        setInterval(fetchMonitoringData, 5000);
    </script>

    @if(env('FIREBASE_PROJECT_ID'))
    <!-- Firebase Cloud Messaging Setup -->
    <script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-messaging-compat.js"></script>
    <script>
        window.firebaseConfig = {
            apiKey: "{{ env('FIREBASE_API_KEY') }}",
            authDomain: "{{ env('FIREBASE_AUTH_DOMAIN') }}",
            projectId: "{{ env('FIREBASE_PROJECT_ID') }}",
            storageBucket: "{{ env('FIREBASE_STORAGE_BUCKET') }}",
            messagingSenderId: "{{ env('FIREBASE_MESSAGING_SENDER_ID') }}",
            appId: "{{ env('FIREBASE_APP_ID') }}"
        };
        window.firebaseVapidKey = "{{ env('FIREBASE_VAPID_KEY') }}";

        if (window.firebaseConfig.apiKey) {
            firebase.initializeApp(window.firebaseConfig);
            const messaging = firebase.messaging();

            if ('serviceWorker' in navigator) {
                const params = new URLSearchParams(window.firebaseConfig).toString();
                navigator.serviceWorker.register('/firebase-messaging-sw.js?' + params)
                    .then((registration) => {
                        console.log('FCM Service Worker registered:', registration);
                        messaging.useServiceWorker(registration);
                        requestFcmToken();
                    })
                    .catch((err) => {
                        console.error('FCM Service Worker registration failed:', err);
                    });
            }

            function requestFcmToken() {
                Notification.requestPermission().then((permission) => {
                    if (permission === 'granted') {
                        messaging.getToken({ vapidKey: window.firebaseVapidKey }).then((currentToken) => {
                            if (currentToken) {
                                console.log('FCM Token:', currentToken);
                                fetch('/api/fcm/register', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json'
                                    },
                                    body: JSON.stringify({ token: currentToken })
                                })
                                .then(res => res.json())
                                .then(data => console.log('FCM Token registered to server:', data))
                                .catch(err => console.error('Error registering FCM token:', err));
                            } else {
                                console.warn('No registration token available.');
                            }
                        }).catch((err) => {
                            console.error('An error occurred while retrieving token:', err);
                        });
                    } else {
                        console.warn('Notification permission not granted.');
                    }
                });
            }

            // Foreground Message Handler
            messaging.onMessage((payload) => {
                console.log('Foreground message received:', payload);
                if (Notification.permission === 'granted') {
                    new Notification(payload.notification.title, {
                        body: payload.notification.body,
                        icon: payload.notification.icon || '/uhn_logo.png'
                    });
                }
            });
        }
    </script>
    @endif
</body>
</html>