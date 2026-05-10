const dashboardPage = document.querySelector('[data-dashboard-url]');

if (dashboardPage) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
    const dataUrl = dashboardPage.dataset.dashboardUrl;
    const toggleBaseUrl = dashboardPage.dataset.toggleBase;
    const defaultSensorRange = dashboardPage.dataset.defaultSensorRange || '25m';
    const navLinks = [...document.querySelectorAll('[data-nav-link]')];
    const sensorRangeButtons = [...document.querySelectorAll('[data-sensor-range]')];
    const sidebarToggle = document.querySelector('[data-sidebar-toggle]');
    const sidebarBackdrop = document.querySelector('[data-sidebar-backdrop]');
    const sidebarCloseButtons = [...document.querySelectorAll('[data-sidebar-close]')];
    const mobileSidebarQuery = window.matchMedia('(max-width: 920px)');
    const sensorRangeLabels = {
        '1m': '1 Menit Terakhir',
        '5m': '5 Menit Terakhir',
        '25m': '25 Menit Terakhir',
        '1h': '1 Jam Terakhir',
        '1d': '1 Hari Terakhir',
    };
    const maxVisibleReadings = 80;
    let selectedSensorRange = defaultSensorRange;
    let sensorChart = null;

    const setConnection = (online) => {
        setText('apiStatus', online ? 'Terhubung' : 'Gagal');
    };

    const setText = (id, value) => {
        const element = document.getElementById(id);

        if (element) {
            element.textContent = value;
        }
    };

    const setSidebarOpen = (open) => {
        dashboardPage.classList.toggle('sidebar-open', open);
        sidebarToggle?.setAttribute('aria-expanded', String(open));

        if (sidebarBackdrop) {
            sidebarBackdrop.hidden = !open;
        }
    };

    const renderLatest = (latest) => {
        if (!latest) {
            setText('temperatureValue', '--');
            setText('humidityValue', '--');
            setText('latestTemperatureTime', 'Belum ada data sensor');
            setText('latestHumidityTime', 'Menunggu NodeMCU');

            return;
        }

        setText('temperatureValue', Number(latest.suhu).toFixed(1));
        setText('humidityValue', Number(latest.kelembaban).toFixed(1));
        setText('latestTemperatureTime', latest.label);
        setText('latestHumidityTime', latest.label);
    };

    const renderPumpStatus = (pump) => {
        if (!pump) {
            return;
        }

        const isOn = Boolean(pump.effective_active);
        const activeSchedule = pump.active_schedule;
        let note = 'Menunggu jadwal/manual';

        if (pump.manual_active) {
            note = 'Tombol manual aktif';
        } else if (pump.automatic_active && activeSchedule) {
            note = `${activeSchedule.name} · ${activeSchedule.start_time} · ${activeSchedule.duration_minutes} menit`;
        }

        setText('pumpMode', isOn ? pump.source : 'OFF');
        setText('pumpNote', note);
    };

    const renderControls = (controls, manualControls = controls, pump = null) => {
        Object.entries(controls).forEach(([device, status]) => {
            const isOn = Number(status) === 1;
            const isManualOn = Number(manualControls?.[device] ?? status) === 1;
            const toggle = document.querySelector(`[data-device="${device}"]`);
            const label = document.querySelector(`[data-device-status="${device}"]`);

            if (toggle) {
                toggle.checked = isManualOn;
            }

            if (label) {
                label.textContent = isOn ? 'ON' : 'OFF';

                if (device === 'pompa' && isOn && pump?.source) {
                    label.textContent = `ON (${pump.source})`;
                }

                label.style.color = isOn ? 'var(--success)' : 'var(--muted)';
            }
        });

        renderPumpStatus(pump);
    };

    const syncSensorRangeControls = () => {
        sensorRangeButtons.forEach((button) => {
            const isActive = button.dataset.sensorRange === selectedSensorRange;

            button.classList.toggle('active', isActive);
            button.setAttribute('aria-pressed', String(isActive));
        });

        setText('sensorRangeLabel', sensorRangeLabels[selectedSensorRange] ?? sensorRangeLabels[defaultSensorRange]);
    };

    const dashboardDataUrl = () => {
        const url = new URL(dataUrl, window.location.origin);

        url.searchParams.set('sensor_range', selectedSensorRange);

        return url.toString();
    };

    const renderChart = (readings) => {
        const canvas = document.getElementById('sensorChart');

        if (!canvas || !window.Chart) {
            return;
        }

        const visibleReadings = (readings ?? []).slice(-maxVisibleReadings);
        const pointRadius = visibleReadings.length > 30 ? 0 : 2;
        const labels = visibleReadings.map((reading) => reading.label);
        const temperatures = visibleReadings.map((reading) => reading.suhu);
        const humidity = visibleReadings.map((reading) => reading.kelembaban);

        if (!sensorChart) {
            sensorChart = new window.Chart(canvas, {
                type: 'line',
                data: {
                    labels,
                    datasets: [
                        {
                            label: 'Suhu',
                            data: temperatures,
                            borderColor: '#e88b23',
                            backgroundColor: 'rgba(232, 139, 35, 0.12)',
                            tension: 0.36,
                            fill: true,
                            pointRadius,
                            pointHoverRadius: 5,
                        },
                        {
                            label: 'Kelembaban',
                            data: humidity,
                            borderColor: '#0d8b6f',
                            backgroundColor: 'rgba(13, 139, 111, 0.12)',
                            tension: 0.36,
                            fill: true,
                            pointRadius,
                            pointHoverRadius: 5,
                        },
                    ],
                },
                options: {
                    maintainAspectRatio: false,
                    responsive: true,
                    interaction: {
                        intersect: false,
                        mode: 'index',
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false,
                            },
                            ticks: {
                                maxTicksLimit: 8,
                            },
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(98, 112, 107, 0.14)',
                            },
                        },
                    },
                    plugins: {
                        legend: {
                            labels: {
                                boxWidth: 12,
                                usePointStyle: true,
                            },
                        },
                    },
                },
            });

            return;
        }

        sensorChart.data.labels = labels;
        sensorChart.data.datasets[0].data = temperatures;
        sensorChart.data.datasets[1].data = humidity;
        sensorChart.data.datasets[0].pointRadius = pointRadius;
        sensorChart.data.datasets[1].pointRadius = pointRadius;
        sensorChart.update();
    };

    const loadDashboard = async () => {
        try {
            const response = await fetch(dashboardDataUrl(), {
                headers: {
                    Accept: 'application/json',
                },
            });

            if (!response.ok) {
                throw new Error(`Dashboard response ${response.status}`);
            }

            const payload = await response.json();
            renderLatest(payload.latest);
            renderControls(payload.controls, payload.manual_controls, payload.pump);
            renderChart(payload.readings);
            setConnection(true);
            setText('lastRefresh', `Refresh ${new Date().toLocaleTimeString('id-ID')}`);
        } catch (error) {
            setConnection(false);
        }
    };

    sensorRangeButtons.forEach((button) => {
        button.addEventListener('click', () => {
            selectedSensorRange = button.dataset.sensorRange || defaultSensorRange;
            syncSensorRangeControls();
            loadDashboard();
        });
    });

    document.querySelectorAll('.control-toggle').forEach((toggle) => {
        toggle.addEventListener('change', async (event) => {
            const input = event.currentTarget;
            const previousValue = !input.checked;

            input.disabled = true;

            try {
                const response = await fetch(`${toggleBaseUrl}/${input.dataset.device}`, {
                    method: 'PATCH',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        status: input.checked ? 1 : 0,
                    }),
                });

                if (!response.ok) {
                    throw new Error(`Control response ${response.status}`);
                }

                const payload = await response.json();
                renderControls(payload.controls, payload.manual_controls, payload.pump);
                setConnection(true);
            } catch (error) {
                input.checked = previousValue;
                setConnection(false);
            } finally {
                input.disabled = false;
            }
        });
    });

    navLinks.forEach((link) => {
        link.addEventListener('click', () => {
            if (mobileSidebarQuery.matches) {
                setSidebarOpen(false);
            }
        });
    });

    sidebarToggle?.addEventListener('click', () => {
        setSidebarOpen(!dashboardPage.classList.contains('sidebar-open'));
    });

    sidebarCloseButtons.forEach((button) => {
        button.addEventListener('click', () => setSidebarOpen(false));
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            setSidebarOpen(false);
        }
    });

    const handleSidebarBreakpoint = (event) => {
        if (!event.matches) {
            setSidebarOpen(false);
        }
    };

    if (mobileSidebarQuery.addEventListener) {
        mobileSidebarQuery.addEventListener('change', handleSidebarBreakpoint);
    } else {
        mobileSidebarQuery.addListener(handleSidebarBreakpoint);
    }

    syncSensorRangeControls();
    loadDashboard();
    window.setInterval(loadDashboard, 5000);
}

const everyDayToggle = document.getElementById('everyDayToggle');
const dayCheckboxes = [...document.querySelectorAll('.schedule-day')];

if (everyDayToggle && dayCheckboxes.length > 0) {
    const syncEveryDay = () => {
        everyDayToggle.checked = dayCheckboxes.every((checkbox) => checkbox.checked);
    };

    everyDayToggle.addEventListener('change', () => {
        dayCheckboxes.forEach((checkbox) => {
            checkbox.checked = everyDayToggle.checked;
        });
    });

    dayCheckboxes.forEach((checkbox) => {
        checkbox.addEventListener('change', syncEveryDay);
    });

    syncEveryDay();
}
