const dashboardPage = document.querySelector('[data-dashboard-url]');

if (dashboardPage) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
    const dataUrl = dashboardPage.dataset.dashboardUrl;
    const toggleBaseUrl = dashboardPage.dataset.toggleBase;
    const lampBulkUrl = dashboardPage.dataset.lampBulkUrl;
    const defaultSensorRange = dashboardPage.dataset.defaultSensorRange || '25m';
    const navLinks = [...document.querySelectorAll('[data-nav-link]')];
    const sensorRangeButtons = [...document.querySelectorAll('[data-sensor-range]')];
    const sidebarToggle = document.querySelector('[data-sidebar-toggle]');
    const sidebar = document.getElementById('sidebar');
    const menuToggle = document.getElementById('menuToggle');
    const desktopSidebarToggle = document.getElementById('desktopSidebarToggle');
    const desktopSidebarToggleLabel = document.getElementById('desktopSidebarToggleLabel');
    const sidebarBackdrop = document.querySelector('[data-sidebar-backdrop]') ?? document.getElementById('sidebarBackdrop');
    const sidebarCloseButtons = [...document.querySelectorAll('[data-sidebar-close]')];
    const mobileSidebarQuery = window.matchMedia('(max-width: 768px)');
    const sensorRangeLabels = {
        '1m': '1 Menit Terakhir',
        '5m': '5 Menit Terakhir',
        '25m': '25 Menit Terakhir',
        '1h': '1 Jam Terakhir',
        '1d': '1 Hari Terakhir',
    };
    const lampDeviceNames = ['lampu1', 'lampu2', 'lampu3'];
    const maxVisibleReadings = 80;
    let selectedSensorRange = defaultSensorRange;

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
        sidebar?.classList.toggle('open', open);
        sidebarBackdrop?.classList.toggle('open', open);
        sidebarToggle?.setAttribute('aria-expanded', String(open));

        if (sidebarBackdrop) {
            sidebarBackdrop.hidden = !open;
        }
    };

    const setDesktopSidebarCollapsed = (collapsed) => {
        dashboardPage.classList.toggle('sidebar-collapsed', collapsed);
        desktopSidebarToggle?.setAttribute('aria-pressed', String(collapsed));

        if (desktopSidebarToggleLabel) {
            desktopSidebarToggleLabel.textContent = collapsed ? 'Menu' : 'Full screen';
        }

        try {
            localStorage.setItem('sidebar-collapsed', collapsed ? '1' : '0');
        } catch (error) {
            // localStorage can be unavailable in restricted browser contexts.
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

    const renderLampBulkButton = (manualControls = {}) => {
        const button = document.querySelector('[data-lamp-bulk-toggle]');

        if (!button) {
            return;
        }

        const label = button.querySelector('[data-lamp-bulk-label]');
        const icon = button.querySelector('[data-lamp-bulk-icon]');
        const allManualOn = lampDeviceNames.every((device) => Number(manualControls?.[device] ?? 0) === 1);
        const nextStatus = allManualOn ? 0 : 1;

        button.dataset.lampBulkStatus = String(nextStatus);
        button.classList.toggle('secondary', allManualOn);

        if (label) {
            label.textContent = allManualOn ? 'Matikan Semua Lampu' : 'Nyalakan Semua Lampu';
        }

        if (icon) {
            icon.classList.toggle('bi-lightbulb-fill', !allManualOn);
            icon.classList.toggle('bi-lightbulb-off', allManualOn);
        }
    };

    const renderControls = (controls, manualControls = controls, pump = null, lamp = null) => {
        Object.entries(controls).forEach(([device, status]) => {
            const isOn = Number(status) === 1;
            const isManualOn = Number(manualControls?.[device] ?? status) === 1;
            const isScheduledLamp = Number(lamp?.active_devices?.[device] ?? 0) === 1;
            const toggle = document.querySelector(`[data-device="${device}"]`);
            const label = document.querySelector(`[data-device-status="${device}"]`);

            if (toggle) {
                toggle.checked = isManualOn;
            }

            if (label) {
                label.textContent = isOn ? 'ON' : 'OFF';

                if (device === 'pompa' && isOn && pump?.source) {
                    label.textContent = `ON (${pump.source})`;
                } else if (isScheduledLamp && isOn) {
                    label.textContent = isManualOn ? 'ON (Manual + Jadwal)' : 'ON (Jadwal)';
                }

                label.style.color = isOn ? 'var(--success)' : 'var(--muted)';
            }
        });

        renderLampBulkButton(manualControls);
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
            renderControls(payload.controls, payload.manual_controls, payload.pump, payload.lamp);
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
                renderControls(payload.controls, payload.manual_controls, payload.pump, payload.lamp);
                setConnection(true);
            } catch (error) {
                input.checked = previousValue;
                setConnection(false);
            } finally {
                input.disabled = false;
            }
        });
    });

    document.querySelectorAll('[data-lamp-bulk-toggle]').forEach((button) => {
        button.addEventListener('click', async () => {
            if (!lampBulkUrl) {
                return;
            }

            const status = Number(button.dataset.lampBulkStatus) === 1 ? 1 : 0;

            button.disabled = true;

            try {
                const response = await fetch(lampBulkUrl, {
                    method: 'PATCH',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({ status }),
                });

                if (!response.ok) {
                    throw new Error(`Lamp control response ${response.status}`);
                }

                const payload = await response.json();
                renderControls(payload.controls, payload.manual_controls, payload.pump, payload.lamp);
                setConnection(true);
            } catch (error) {
                setConnection(false);
            } finally {
                button.disabled = false;
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

    try {
        setDesktopSidebarCollapsed(localStorage.getItem('sidebar-collapsed') === '1');
    } catch (error) {
        setDesktopSidebarCollapsed(false);
    }

    desktopSidebarToggle?.addEventListener('click', () => {
        setDesktopSidebarCollapsed(!dashboardPage.classList.contains('sidebar-collapsed'));
    });

    menuToggle?.addEventListener('click', () => {
        setSidebarOpen(!sidebar?.classList.contains('open'));
    });

    sidebarBackdrop?.addEventListener('click', () => {
        setSidebarOpen(false);
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

document.querySelectorAll('.schedule-days-group').forEach((group) => {
    const everyDayToggle = group.querySelector('.every-day-toggle');
    const dayCheckboxes = [...group.querySelectorAll('.schedule-day')];

    if (!everyDayToggle || dayCheckboxes.length === 0) {
        return;
    }

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
});

const scheduleTabs = [...document.querySelectorAll('[data-schedule-tab]')];
const scheduleSections = [...document.querySelectorAll('[data-schedule-section]')];

if (scheduleTabs.length > 0 && scheduleSections.length > 0) {
    const setScheduleTab = (selectedTab) => {
        scheduleTabs.forEach((button) => {
            const isActive = button.dataset.scheduleTab === selectedTab;

            button.classList.toggle('active', isActive);
            button.setAttribute('aria-pressed', String(isActive));
        });

        scheduleSections.forEach((section) => {
            section.classList.toggle('active', section.dataset.scheduleSection === selectedTab);
        });
    };

    scheduleTabs.forEach((button) => {
        button.addEventListener('click', () => {
            setScheduleTab(button.dataset.scheduleTab || 'lampu');
        });
    });

    setScheduleTab('lampu');
}
