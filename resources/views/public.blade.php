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
    </script>
</body>
</html>