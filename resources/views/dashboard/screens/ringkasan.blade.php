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
            <div class="metric-value compact {{ $deviceConnected ? 'text-success' : 'text-danger' }}" id="apiStatus" style="font-weight: 700;">
                {{ $deviceConnected ? 'TERHUBUNG' : 'TERPUTUS' }}
            </div>
            <p class="metric-note" id="lastRefresh">
                @if ($lastSeen)
                    Terakhir aktif: {{ $lastSeen->timezone(config('app.timezone'))->format('H:i:s') }}
                @else
                    Belum ada koneksi dari alat
                @endif
            </p>
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

    <!-- Content Grid for Chart & Device Status -->
    <div class="content-grid" style="margin-top: 24px;">
        <!-- Chart Section -->
        <section class="chart-panel">
            <!-- Header -->
            <div style="display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:16px; flex-wrap:wrap; gap:12px;">
                <div>
                    <div class="section-label" id="sensorRangeLabel">25 Menit Terakhir</div>
                    <h2 class="section-title">Grafik Sensor</h2>
                </div>
                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                    <!-- Dropdown Unduh PDF -->
                    <div class="dropdown" style="position: relative; display: inline-block;">
                        <button type="button" class="desktop-sidebar-toggle" id="downloadPdfDropdownBtnAdmin" style="display: inline-flex; align-items: center; gap: 6px;">
                            <i class="bi bi-file-earmark-pdf-fill text-danger"></i>
                            <span>Unduh Laporan</span>
                            <i class="bi bi-chevron-down" style="font-size: 10px;"></i>
                        </button>
                        <div class="dropdown-menu-admin" id="dropdownMenuAdmin" style="display: none; position: absolute; right: 0; margin-top: 8px; width: 200px; border-radius: var(--radius-md); border: 1px solid var(--color-border); background: var(--color-surface); box-shadow: var(--shadow-lg); z-index: 50; padding: 6px;">
                            <a href="{{ route('export.pdf', ['range' => 'weekly']) }}" style="display: flex; align-items: center; justify-content: space-between; padding: 8px 12px; text-decoration: none; color: var(--color-text); font-size: 13px; border-radius: var(--radius-sm); transition: background 0.2s;" onmouseover="this.style.background='#f5f5f5'" onmouseout="this.style.background='transparent'">
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <i class="bi bi-file-earmark-pdf-fill text-danger" style="font-size: 14px;"></i>
                                    <span style="font-weight: 500;">Data Mingguan</span>
                                </div>
                                <i class="bi bi-download" style="font-size: 11px; color: var(--color-text-muted);"></i>
                            </a>
                            <a href="{{ route('export.pdf', ['range' => 'monthly']) }}" style="display: flex; align-items: center; justify-content: space-between; padding: 8px 12px; text-decoration: none; color: var(--color-text); font-size: 13px; border-radius: var(--radius-sm); transition: background 0.2s;" onmouseover="this.style.background='#f5f5f5'" onmouseout="this.style.background='transparent'">
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <i class="bi bi-file-earmark-pdf-fill text-danger" style="font-size: 14px;"></i>
                                    <span style="font-weight: 500;">Data Bulanan</span>
                                </div>
                                <i class="bi bi-download" style="font-size: 11px; color: var(--color-text-muted);"></i>
                            </a>
                            <a href="{{ route('export.pdf', ['range' => 'all']) }}" style="display: flex; align-items: center; justify-content: space-between; padding: 8px 12px; text-decoration: none; color: var(--color-text); font-size: 13px; border-radius: var(--radius-sm); transition: background 0.2s;" onmouseover="this.style.background='#f5f5f5'" onmouseout="this.style.background='transparent'">
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <i class="bi bi-file-earmark-pdf-fill text-danger" style="font-size: 14px;"></i>
                                    <span style="font-weight: 500;">Semua Data</span>
                                </div>
                                <i class="bi bi-download" style="font-size: 11px; color: var(--color-text-muted);"></i>
                            </a>
                        </div>
                    </div>
                    
                    <!-- Range buttons -->
                    <div class="chart-range-switch-container" id="rangeSwitch" aria-label="Rentang grafik">
                        <button class="chart-range-btn crb" data-range="1m">1 Menit</button>
                        <button class="chart-range-btn crb" data-range="5m">5 Menit</button>
                        <button class="chart-range-btn crb active" data-range="25m">25 Menit</button>
                        <button class="chart-range-btn crb" data-range="1h">1 Jam</button>
                        <button class="chart-range-btn crb" data-range="1d">1 Hari</button>
                    </div>
                </div>
            </div>

            <!-- Dataset toggle -->
            <div style="display:flex; gap:8px; margin-bottom:14px;">
                <button class="chart-dataset-btn cdb active" data-dataset="both">
                    <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:linear-gradient(135deg,#6dab28,#42a5f5);"></span>
                    Keduanya
                </button>
                <button class="chart-dataset-btn cdb" data-dataset="suhu">
                    <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#6dab28;"></span>
                    Suhu
                </button>
                <button class="chart-dataset-btn cdb" data-dataset="kelembaban">
                    <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#42a5f5;"></span>
                    Kelembaban
                </button>
            </div>

            <!-- Canvas -->
            <div class="chart-box" style="height: 380px;">
                <canvas id="sensorChart" style="max-height: 100%;"></canvas>
            </div>
        </section>

        <!-- Status Perangkat Section -->
        <section class="control-panel">
            <div class="panel-header">
                <div>
                    <div class="eyebrow">Aktif / Nonaktif</div>
                    <h2>Status Perangkat</h2>
                </div>
            </div>

            <div class="control-list">
                <!-- Lampu 1 -->
                @php
                    $isOn1 = ($controls['lampu1'] ?? 0) == 1;
                    $isManual1 = ($manualControls['lampu1'] ?? 0) == 1;
                    $isScheduled1 = ($lampStatus['active_devices']['lampu1'] ?? 0) == 1;
                @endphp
                <div class="control-row">
                    <div class="control-info">
                        <div class="control-name" style="font-size: 14px; font-weight: 600; margin-bottom: 2px;">Lampu Taman 1</div>
                        <div class="control-status" style="font-size: 11px;" id="status-desc-lampu1">
                            @if ($isOn1)
                                @if ($isManual1 && $isScheduled1)
                                    Aktif (Manual + Jadwal)
                                @elseif ($isScheduled1)
                                    Aktif (Jadwal)
                                @else
                                    Aktif (Manual)
                                @endif
                            @else
                                Nonaktif
                            @endif
                        </div>
                    </div>
                    <div>
                        <span id="badge-lampu1" class="badge" 
                            style="font-size: 10px; padding: 4px 8px; border-radius: 4px; font-weight: 700; text-transform: uppercase;
                                    {{ $isOn1 ? 'background: #dcfce7; color: #15803d;' : 'background: #f3f4f6; color: #6b7280;' }}">
                            {{ $isOn1 ? 'AKTIF' : 'MATI' }}
                        </span>
                    </div>
                </div>

                <!-- Lampu 2 -->
                @php
                    $isOn2 = ($controls['lampu2'] ?? 0) == 1;
                    $isManual2 = ($manualControls['lampu2'] ?? 0) == 1;
                    $isScheduled2 = ($lampStatus['active_devices']['lampu2'] ?? 0) == 1;
                @endphp
                <div class="control-row">
                    <div class="control-info">
                        <div class="control-name" style="font-size: 14px; font-weight: 600; margin-bottom: 2px;">Lampu Taman 2</div>
                        <div class="control-status" style="font-size: 11px;" id="status-desc-lampu2">
                            @if ($isOn2)
                                @if ($isManual2 && $isScheduled2)
                                    Aktif (Manual + Jadwal)
                                @elseif ($isScheduled2)
                                    Aktif (Jadwal)
                                @else
                                    Aktif (Manual)
                                @endif
                            @else
                                Nonaktif
                            @endif
                        </div>
                    </div>
                    <div>
                        <span id="badge-lampu2" class="badge" 
                            style="font-size: 10px; padding: 4px 8px; border-radius: 4px; font-weight: 700; text-transform: uppercase;
                                    {{ $isOn2 ? 'background: #dcfce7; color: #15803d;' : 'background: #f3f4f6; color: #6b7280;' }}">
                            {{ $isOn2 ? 'AKTIF' : 'MATI' }}
                        </span>
                    </div>
                </div>

                <!-- Lampu 3 -->
                @php
                    $isOn3 = ($controls['lampu3'] ?? 0) == 1;
                    $isManual3 = ($manualControls['lampu3'] ?? 0) == 1;
                    $isScheduled3 = ($lampStatus['active_devices']['lampu3'] ?? 0) == 1;
                @endphp
                <div class="control-row">
                    <div class="control-info">
                        <div class="control-name" style="font-size: 14px; font-weight: 600; margin-bottom: 2px;">Lampu Taman 3</div>
                        <div class="control-status" style="font-size: 11px;" id="status-desc-lampu3">
                            @if ($isOn3)
                                @if ($isManual3 && $isScheduled3)
                                    Aktif (Manual + Jadwal)
                                @elseif ($isScheduled3)
                                    Aktif (Jadwal)
                                @else
                                    Aktif (Manual)
                                @endif
                            @else
                                Nonaktif
                            @endif
                        </div>
                    </div>
                    <div>
                        <span id="badge-lampu3" class="badge" 
                            style="font-size: 10px; padding: 4px 8px; border-radius: 4px; font-weight: 700; text-transform: uppercase;
                                    {{ $isOn3 ? 'background: #dcfce7; color: #15803d;' : 'background: #f3f4f6; color: #6b7280;' }}">
                            {{ $isOn3 ? 'AKTIF' : 'MATI' }}
                        </span>
                    </div>
                </div>

                <!-- Pompa -->
                @php
                    $isOnPump = ($controls['pompa'] ?? 0) == 1;
                @endphp
                <div class="control-row">
                    <div class="control-info">
                        <div class="control-name" style="font-size: 14px; font-weight: 600; margin-bottom: 2px;">Pompa Penyiraman</div>
                        <div class="control-status" style="font-size: 11px;" id="status-desc-pompa">
                            @if ($isOnPump)
                                Penyiraman aktif ({{ $pumpStatus['source'] }})
                            @else
                                Nonaktif
                            @endif
                        </div>
                    </div>
                    <div>
                        <span id="badge-pompa" class="badge" 
                            style="font-size: 10px; padding: 4px 8px; border-radius: 4px; font-weight: 700; text-transform: uppercase;
                                    {{ $isOnPump ? 'background: #dcfce7; color: #15803d;' : 'background: #f3f4f6; color: #6b7280;' }}">
                            {{ $isOnPump ? 'AKTIF' : 'MATI' }}
                        </span>
                    </div>
                </div>
            </div>
        </section>
    </div>
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
        // Handled via CSS class toggles, clear inline style overrides
        document.querySelectorAll('.crb').forEach(btn => {
            btn.style.background = '';
            btn.style.color      = '';
            btn.style.boxShadow  = '';
        });
    }

    // ── Load data ─────────────────────────────────────────────
    async function loadSensorData(range = '25m') {
        try {
            const res  = await fetch(`/dashboard/data?sensor_range=${range}`);
            const data = await res.json();
            
            // Update chart
            sensorChart.data.labels            = data.readings.map(r => r.label);
            sensorChart.data.datasets[0].data  = data.readings.map(r => r.suhu);
            sensorChart.data.datasets[1].data  = data.readings.map(r => r.kelembaban);
            applyDatasetVisibility();

            // Update metrics cards
            if (data.latest) {
                const tempVal = document.getElementById('temperatureValue');
                if (tempVal) tempVal.textContent = Number(data.latest.suhu).toFixed(1);
                
                const humidVal = document.getElementById('humidityValue');
                if (humidVal) humidVal.textContent = Number(data.latest.kelembaban).toFixed(1);

                const tempTime = document.getElementById('latestTemperatureTime');
                if (tempTime) tempTime.textContent = data.latest.label;

                const humidTime = document.getElementById('latestHumidityTime');
                if (humidTime) humidTime.textContent = data.latest.label;
            } else {
                const tempVal = document.getElementById('temperatureValue');
                if (tempVal) tempVal.textContent = '--';
                
                const humidVal = document.getElementById('humidityValue');
                if (humidVal) humidVal.textContent = '--';

                const tempTime = document.getElementById('latestTemperatureTime');
                if (tempTime) tempTime.textContent = 'Belum ada data sensor';

                const humidTime = document.getElementById('latestHumidityTime');
                if (humidTime) humidTime.textContent = 'Menunggu NodeMCU';
            }

            // Update Connection Status
            const apiStatus = document.getElementById('apiStatus');
            if (apiStatus) {
                const isConnected = !!data.device_connected;
                apiStatus.textContent = isConnected ? 'TERHUBUNG' : 'TERPUTUS';
                apiStatus.className = `metric-value compact ${isConnected ? 'text-success' : 'text-danger'}`;
                
                const lastRefresh = document.getElementById('lastRefresh');
                if (lastRefresh) {
                    if (data.device_last_seen) {
                        lastRefresh.textContent = `Terakhir aktif: ${data.device_last_seen}`;
                    } else {
                        lastRefresh.textContent = 'Belum ada koneksi dari alat';
                    }
                }
            }

            // Update Pump Mode & Note
            if (data.pump) {
                const pumpMode = document.getElementById('pumpMode');
                if (pumpMode) {
                    pumpMode.textContent = data.pump.effective_active ? data.pump.source : 'OFF';
                }
                const pumpNote = document.getElementById('pumpNote');
                if (pumpNote) {
                    let note = 'Menunggu jadwal/manual';
                    if (data.pump.manual_active) {
                        note = 'Tombol manual aktif';
                    } else if (data.pump.automatic_active && data.pump.active_schedule) {
                        note = `${data.pump.active_schedule.name} · ${data.pump.active_schedule.start_time} · ${data.pump.active_schedule.duration_minutes} menit`;
                    }
                    pumpNote.textContent = note;
                }
            }

            // Update devices status list (Status Perangkat list)
            if (data.controls && data.manual_controls && data.lamp) {
                const deviceNames = ['lampu1', 'lampu2', 'lampu3', 'pompa'];
                deviceNames.forEach(device => {
                    const badge = document.getElementById(`badge-${device}`);
                    const desc = document.getElementById(`status-desc-${device}`);
                    if (badge && desc) {
                        const isActive = Number(data.controls[device]) === 1;
                        const isManual = Number(data.manual_controls[device]) === 1;
                        const isLamp = device.startsWith('lampu');
                        
                        if (isActive) {
                            badge.textContent = "AKTIF";
                            badge.style.background = "#dcfce7";
                            badge.style.color = "#15803d";
                            
                            if (isLamp) {
                                const isScheduledLamp = Number(data.lamp?.active_devices?.[device] ?? 0) === 1;
                                if (isScheduledLamp && isManual) {
                                    desc.textContent = "Aktif (Manual + Jadwal)";
                                } else if (isScheduledLamp) {
                                    desc.textContent = "Aktif (Jadwal)";
                                } else {
                                    desc.textContent = "Aktif (Manual)";
                                }
                            } else {
                                desc.textContent = `Penyiraman aktif (${data.pump?.source || 'Manual'})`;
                            }
                        } else {
                            badge.textContent = "MATI";
                            badge.style.background = "#f3f4f6";
                            badge.style.color = "#6b7280";
                            desc.textContent = "Nonaktif";
                        }
                    }
                });
            }
        } catch (e) {
            console.error('Error:', e);
            const apiStatus = document.getElementById('apiStatus');
            if (apiStatus) {
                apiStatus.textContent = 'TERPUTUS';
                apiStatus.className = 'metric-value compact text-danger';
                const lastRefresh = document.getElementById('lastRefresh');
                if (lastRefresh) {
                    lastRefresh.textContent = 'Koneksi ke server gagal';
                }
            }
        }
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
    });

    // ── Auto-refresh & init ───────────────────────────────────
    setInterval(() => {
        const r = document.querySelector('.crb.active')?.dataset.range ?? '25m';
        loadSensorData(r);
    }, 5000);

    loadSensorData('25m');

    // Dropdown toggle logic
    const dropdownBtnAdmin = document.getElementById('downloadPdfDropdownBtnAdmin');
    const dropdownMenuAdmin = document.getElementById('dropdownMenuAdmin');
    if (dropdownBtnAdmin && dropdownMenuAdmin) {
        dropdownBtnAdmin.addEventListener('click', (e) => {
            e.stopPropagation();
            if (dropdownMenuAdmin.style.display === 'none' || dropdownMenuAdmin.style.display === '') {
                dropdownMenuAdmin.style.display = 'block';
            } else {
                dropdownMenuAdmin.style.display = 'none';
            }
        });
        document.addEventListener('click', () => {
            dropdownMenuAdmin.style.display = 'none';
        });
    }
    </script>