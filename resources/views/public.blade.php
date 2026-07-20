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

        /* ── PDF Dropdown Smooth Animation ─────────────────────────── */
        @keyframes dropdownIn {
            from { opacity: 0; transform: translateY(-8px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes dropdownOut {
            from { opacity: 1; transform: translateY(0); }
            to   { opacity: 0; transform: translateY(-8px); }
        }
        .pdf-dropdown-panel.entering {
            animation: dropdownIn 0.2s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        .pdf-dropdown-panel.leaving {
            animation: dropdownOut 0.15s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        .pdf-dropdown-trigger[aria-expanded="true"] .pdf-chevron {
            transform: rotate(180deg);
        }
    </style>
</head>
<body class="dashboard-page min-h-screen pb-16 bg-gray-50/50">

    <x-navbar />

    <div class="public-layout mt-6">
        
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
            <div>
                <div class="page-label text-xs font-bold tracking-wider text-gray-400 uppercase mb-1">Live Monitoring</div>
                <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Kondisi Realtime Taman</h1>
                <p class="text-sm text-gray-500 mt-1">Pantau kelembaban, suhu, dan status aktif perangkat secara live.</p>
            </div>
            
            <div class="flex items-center flex-wrap gap-3">
                <div class="flex items-center gap-2 bg-emerald-50 border border-emerald-100/80 px-3.5 py-1.5 rounded-full text-emerald-700 text-xs font-bold shadow-sm">
                    <span class="relative flex h-2.5 w-2.5">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
                    </span>
                    <span>Pembaruan Otomatis</span>
                </div>
                
                <div class="relative inline-block text-left" id="pdfDropdownWrapper">
                    <button type="button" id="downloadPdfDropdownBtn" aria-expanded="false" aria-haspopup="true"
                        class="pdf-dropdown-trigger inline-flex items-center gap-2.5 bg-white border border-gray-200 hover:border-gray-300 hover:bg-gray-50 text-gray-700 text-xs font-semibold rounded-xl px-4 py-2.5 transition-all duration-150 shadow-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/10">
                        <i class="bi bi-file-earmark-pdf-fill text-red-500 text-sm"></i>
                        <span>Unduh PDF</span>
                        <i class="bi bi-chevron-down text-gray-400 text-[10px] pdf-chevron transition-transform duration-200"></i>
                    </button>

                    <div id="pdfDropdownMenu"
                        class="pdf-dropdown-panel absolute right-0 z-50 hidden mt-1.5 w-56 origin-top-right rounded-xl bg-white border border-gray-200 shadow-xl ring-1 ring-black ring-opacity-5 focus:outline-none">
                        
                        <div class="px-4 py-2.5 border-b border-gray-100">
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Pilih Rentang</span>
                        </div>

                        <div class="p-1.5 space-y-0.5">
                            <a href="{{ route('export.pdf', ['range' => 'weekly']) }}"
                                class="flex items-center gap-3 px-3 py-2 text-xs text-gray-700 font-medium rounded-lg hover:bg-gray-50 hover:text-gray-900 transition-colors duration-150"
                                role="menuitem">
                                <i class="bi bi-calendar-week text-gray-400 text-sm w-4 text-center"></i>
                                <span>Mingguan</span>
                                <span class="ml-auto text-[10px] text-gray-400 bg-gray-100 px-1.5 py-0.5 rounded font-normal">7 hari</span>
                            </a>
                            
                            <a href="{{ route('export.pdf', ['range' => 'monthly']) }}"
                                class="flex items-center gap-3 px-3 py-2 text-xs text-gray-700 font-medium rounded-lg hover:bg-gray-50 hover:text-gray-900 transition-colors duration-150"
                                role="menuitem">
                                <i class="bi bi-calendar-month text-gray-400 text-sm w-4 text-center"></i>
                                <span>Bulanan</span>
                                <span class="ml-auto text-[10px] text-gray-400 bg-gray-100 px-1.5 py-0.5 rounded font-normal">30 hari</span>
                            </a>
                            
                            <a href="{{ route('export.pdf', ['range' => 'all']) }}"
                                class="flex items-center gap-3 px-3 py-2 text-xs text-gray-700 font-medium rounded-lg hover:bg-gray-50 hover:text-gray-900 transition-colors duration-150"
                                role="menuitem">
                                <i class="bi bi-database text-gray-400 text-sm w-4 text-center"></i>
                                <span>Semua Data</span>
                                <span class="ml-auto text-[10px] text-gray-400 bg-gray-100 px-1.5 py-0.5 rounded font-normal">Semua</span>
                            </a>
                        </div>
                    </div>
                </div>
                </div>
        </div>

        <div class="metrics-grid mb-8">
            <div class="metric-card temperature">
                <div class="metric-header">
                    <div class="metric-label">Suhu Lingkungan</div>
                    <div class="metric-icon"><i class="bi bi-thermometer-half"></i></div>
                </div>
                <div class="metric-value"><span id="live-suhu">--</span><span class="metric-unit">&deg;C</span></div>
                <p class="metric-note text-xs text-gray-400 mt-1" id="latest-suhu-time">Menghubungkan ke NodeMCU...</p>
            </div>

            <div class="metric-card humidity">
                <div class="metric-header">
                    <div class="metric-label">Kelembaban Udara</div>
                    <div class="metric-icon"><i class="bi bi-droplet-half"></i></div>
                </div>
                <div class="metric-value"><span id="live-kelembaban">--</span><span class="metric-unit">%</span></div>
                <p class="metric-note text-xs text-gray-400 mt-1" id="latest-kelembaban-time">Menghubungkan ke NodeMCU...</p>
            </div>

            <div class="metric-card pump">
                <div class="metric-header">
                    <div class="metric-label">Status Pompa</div>
                    <div class="metric-icon"><i class="bi bi-power"></i></div>
                </div>
                <div class="metric-value compact text-gray-500" id="pumpMetricValue">--</div>
                <p class="metric-note text-xs text-gray-400 mt-1" id="pumpMetricNote">Memuat status pompa...</p>
            </div>

            <div class="metric-card lamp">
                <div class="metric-header">
                    <div class="metric-label">Status Lampu</div>
                    <div class="metric-icon"><i class="bi bi-lightbulb"></i></div>
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
                    <div class="metric-icon"><i class="bi bi-router"></i></div>
                </div>
                <div class="metric-value compact text-yellow-600" id="apiStatus">Memuat</div>
                <p class="metric-note text-xs text-gray-400 mt-1" id="lastRefresh">Memuat data terbaru...</p>
            </div>

            {{-- === KARTU STATUS AIR (Guest) === --}}
            <div class="metric-card water">
                <div class="metric-header">
                    <div class="metric-label">Status Air</div>
                    <div class="metric-icon"><i class="bi bi-water"></i></div>
                </div>
                <div class="metric-value compact text-gray-400" id="waterStatusValue" style="font-weight: 700; font-size: 1.35rem;">--</div>
                <div style="margin-top: 10px;">
                    <div id="waterLevelBarWrap" style="background: #f0f4ff; border-radius: 8px; height: 10px; overflow: hidden; width: 100%;">
                        <div id="waterLevelBar" style="height:100%; width:0%; border-radius:8px; background:#d1d5db; transition: width 0.6s ease, background 0.6s ease;"></div>
                    </div>
                    <p class="metric-note text-xs text-gray-400 mt-1.5" id="waterStatusNote">Menunggu data ultrasonic...</p>
                </div>
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
                        <div class="chart-range-switch-container" id="rangeSwitch" aria-label="Rentang grafik">
                            <button class="chart-range-btn crb" data-range="1m">1m</button>
                            <button class="chart-range-btn crb" data-range="5m">5m</button>
                            <button class="chart-range-btn crb active" data-range="25m">25m</button>
                            <button class="chart-range-btn crb" data-range="1h">1h</button>
                            <button class="chart-range-btn crb" data-range="1d">1d</button>
                        </div>
                    </div>

                    <div class="flex gap-2 mb-4">
                        <button class="chart-dataset-btn cdb active" data-dataset="both">
                            <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:linear-gradient(135deg,#6dab28,#42a5f5);"></span> Keduanya
                        </button>
                        <button class="chart-dataset-btn cdb" data-dataset="suhu">
                            <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#6dab28;"></span> Suhu
                        </button>
                        <button class="chart-dataset-btn cdb" data-dataset="kelembaban">
                            <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#42a5f5;"></span> Kelembaban
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
                            <div><span id="badge-lampu1" class="badge bg-secondary px-2.5 py-1 text-[10px] rounded-full">MEMUAT...</span></div>
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
                            <div><span id="badge-lampu2" class="badge bg-secondary px-2.5 py-1 text-[10px] rounded-full">MEMUAT...</span></div>
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
                            <div><span id="badge-lampu3" class="badge bg-secondary px-2.5 py-1 text-[10px] rounded-full">MEMUAT...</span></div>
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
                            <div><span id="badge-pompa" class="badge bg-secondary px-2.5 py-1 text-[10px] rounded-full">MEMUAT...</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-footer />

    <script>
        // ── PDF Dropdown Interactivity Logic ─────────────────────────
        const btn = document.getElementById('downloadPdfDropdownBtn');
        const menu = document.getElementById('pdfDropdownMenu');

        function toggleDropdown() {
            const isExpanded = btn.getAttribute('aria-expanded') === 'true';
            if (isExpanded) {
                btn.setAttribute('aria-expanded', 'false');
                menu.classList.remove('entering');
                menu.classList.add('leaving');
                setTimeout(() => {
                    if (btn.getAttribute('aria-expanded') === 'false') {
                        menu.classList.add('hidden');
                    }
                }, 150);
            } else {
                btn.setAttribute('aria-expanded', 'true');
                menu.classList.remove('hidden', 'leaving');
                menu.classList.add('entering');
            }
        }

        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            toggleDropdown();
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (!document.getElementById('pdfDropdownWrapper').contains(e.target)) {
                if (btn.getAttribute('aria-expanded') === 'true') {
                    toggleDropdown();
                }
            }
        });

        // ── Charts & Plugins Helpers (Sisa dari script lama) ──────────
        function makeGradient(ctx, chartArea, r, g, b, top, bot) {
            const g2 = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
            g2.addColorStop(0,   `rgba(${r},${g},${b},${top})`);
            g2.addColorStop(0.5, `rgba(${r},${g},${b},${top * 0.38})`);
            g2.addColorStop(1,   `rgba(${r},${g},${b},${bot})`);
            return g2;
        }

        const gradientPlugin = {
            id: 'gradientBg',
            afterLayout(chart) {
                const { ctx, chartArea } = chart;
                if (!chartArea) return;
                chart.data.datasets[0].backgroundColor = makeGradient(ctx, chartArea, 109, 171, 40,  0.58, 0);
                chart.data.datasets[1].backgroundColor = makeGradient(ctx, chartArea, 66,  165, 245, 0.45, 0);
            }
        };

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

                // 5. Update Status Air card
                if (data.latest) {
                    const waterVal  = document.getElementById('waterStatusValue');
                    const waterNote = document.getElementById('waterStatusNote');
                    const waterBar  = document.getElementById('waterLevelBar');

                    const labelMap     = { 'FULL': 'Tinggi', 'SEDANG': 'Sedang', 'HABIS': 'Rendah', 'TIDAK TERBACA': 'Tidak Terbaca' };
                    const colorMap     = { 'FULL': '#3b82f6', 'SEDANG': '#f59e0b', 'HABIS': '#ef4444' };
                    const widthMap     = { 'FULL': '90%', 'SEDANG': '50%', 'HABIS': '15%' };
                    const textColorMap = { 'FULL': '#1d4ed8', 'SEDANG': '#b45309', 'HABIS': '#dc2626' };

                    const raw = data.latest.status_air || null;
                    if (waterVal) {
                        waterVal.textContent = raw ? (labelMap[raw] || raw) : '--';
                        waterVal.style.color = raw ? (textColorMap[raw] || '#9ca3af') : '#9ca3af';
                    }
                    if (waterNote) {
                        waterNote.textContent = data.latest.jarak_air
                            ? `Jarak sensor: ${Number(data.latest.jarak_air).toFixed(1)} cm`
                            : 'Menunggu data ultrasonic';
                    }
                    if (waterBar && raw) {
                        waterBar.style.width      = widthMap[raw] || '0%';
                        waterBar.style.background = colorMap[raw] || '#d1d5db';
                    } else if (waterBar) {
                        waterBar.style.width      = '0%';
                        waterBar.style.background = '#d1d5db';
                    }
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

    @if(config('firebase.project_id'))
    <!-- Firebase Cloud Messaging Setup -->
    <script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-messaging-compat.js"></script>
    <script>
        window.firebaseConfig = {
            apiKey: "{{ config('firebase.api_key') }}",
            authDomain: "{{ config('firebase.auth_domain') }}",
            projectId: "{{ config('firebase.project_id') }}",
            storageBucket: "{{ config('firebase.storage_bucket') }}",
            messagingSenderId: "{{ config('firebase.messaging_sender_id') }}",
            appId: "{{ config('firebase.app_id') }}"
        };
        window.firebaseVapidKey = "{{ config('firebase.vapid_key') }}";

        if (window.firebaseConfig.apiKey) {
            firebase.initializeApp(window.firebaseConfig);
            const messaging = firebase.messaging();

            if ('serviceWorker' in navigator) {
                const params = new URLSearchParams(window.firebaseConfig).toString();
                navigator.serviceWorker.register('/firebase-messaging-sw.js?' + params)
                    .then((registration) => {
                        console.log('FCM Service Worker registered:', registration);
                        requestFcmToken(registration);
                    })
                    .catch((err) => {
                        console.error('FCM Service Worker registration failed:', err);
                    });
            }

            function requestFcmToken(registration) {
                Notification.requestPermission().then((permission) => {
                    if (permission === 'granted') {
                        messaging.getToken({ 
                            vapidKey: window.firebaseVapidKey,
                            serviceWorkerRegistration: registration
                        }).then((currentToken) => {
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