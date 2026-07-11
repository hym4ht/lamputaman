<!-- Control Panel -->
<section class="chart-section">
    <div class="section-header with-actions">
        <div>
            <div class="section-label">Relay Active Low</div>
            <h2 class="section-title">Kontrol Perangkat</h2>
        </div>

        @php
            $allLampManualOn = collect(array_keys($lampDevices))
                ->every(fn (string $device): bool => (bool) ($manualControls[$device] ?? false));
        @endphp
        <div class="control-actions" aria-label="Kontrol semua lampu">
            <button class="btn-custom-primary {{ $allLampManualOn ? 'secondary' : '' }}"
                    type="button"
                    data-lamp-bulk-toggle
                    data-lamp-bulk-status="{{ $allLampManualOn ? 0 : 1 }}">
                <i class="bi {{ $allLampManualOn ? 'bi-lightbulb-off' : 'bi-lightbulb-fill' }}" data-lamp-bulk-icon></i>
                <span data-lamp-bulk-label>{{ $allLampManualOn ? 'Matikan Semua Lampu' : 'Nyalakan Semua Lampu' }}</span>
            </button>
        </div>
    </div>

    <div class="control-list">
        @foreach ($devices as $device => $label)
            @php
                $isLamp = array_key_exists($device, $lampDevices);
                $isScheduledLamp = $isLamp && (bool) ($lampStatus['active_devices'][$device] ?? false);
            @endphp
            <div class="control-item">
                <div class="control-info">
                    <div class="control-name">{{ $label }}</div>
                    <div class="control-status {{ ($controls[$device] ?? 0) ? 'on' : '' }}" data-device-status="{{ $device }}">
                        @if ($device === 'pompa' && ($controls[$device] ?? 0))
                            ON ({{ $pumpStatus['source'] }})
                        @elseif ($isScheduledLamp && ($controls[$device] ?? 0))
                            ON ({{ ($manualControls[$device] ?? 0) ? 'Manual + Jadwal' : 'Jadwal' }})
                        @else
                            {{ ($controls[$device] ?? 0) ? 'ON' : 'OFF' }}
                        @endif
                    </div>
                </div>

                <label class="toggle-switch">
                    <input type="checkbox" 
                           class="control-toggle"
                           data-device="{{ $device }}"
                           @checked($manualControls[$device] ?? false)>
                    <span class="toggle-slider"></span>
                </label>
            </div>
        @endforeach
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

    // Handle toggle switch change
    document.querySelectorAll('.control-toggle').forEach(checkbox => {
        checkbox.addEventListener('change', async function() {
            const device = this.dataset.device;
            const status = this.checked ? 1 : 0;
            try {
                const res = await fetch(`/dashboard/control/${device}`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ status })
                });
                if (!res.ok) throw new Error('Failed to update control');
                
                // Immediately poll controls to sync state
                await pollControls();
            } catch (e) {
                console.error(e);
                this.checked = !this.checked; // Revert checkbox state
                alert('Gagal mengirim perintah ke server.');
            }
        });
    });

    // Handle bulk toggle button click
    const bulkBtn = document.querySelector('[data-lamp-bulk-toggle]');
    if (bulkBtn) {
        bulkBtn.addEventListener('click', async function() {
            const nextStatus = parseInt(this.dataset.lampBulkStatus, 10);
            try {
                const res = await fetch('/dashboard/control-lamps', {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ status: nextStatus })
                });
                if (!res.ok) throw new Error('Bulk update failed');
                
                // Refresh states
                await pollControls();
            } catch (e) {
                console.error(e);
                alert('Gagal mengirim perintah ke server.');
            }
        });
    }

    // Periodic state polling
    async function pollControls() {
        try {
            const res = await fetch('/dashboard/data');
            const data = await res.json();
            
            if (data.controls && data.manual_controls) {
                const deviceNames = ['lampu1', 'lampu2', 'lampu3', 'pompa'];
                deviceNames.forEach(device => {
                    const toggle = document.querySelector(`.control-toggle[data-device="${device}"]`);
                    const label = document.querySelector(`[data-device-status="${device}"]`);
                    
                    const isActive = Number(data.controls[device]) === 1;
                    const isManual = Number(data.manual_controls[device]) === 1;
                    const isLamp = device.startsWith('lampu');
                    
                    if (toggle) {
                        toggle.checked = isManual;
                    }
                    
                    if (label) {
                        if (isActive) {
                            label.classList.add('on');
                            if (device === 'pompa' && data.pump?.source) {
                                label.textContent = `ON (${data.pump.source})`;
                            } else if (isLamp && Number(data.lamp?.active_devices?.[device] ?? 0) === 1) {
                                label.textContent = isManual ? 'ON (Manual + Jadwal)' : 'ON (Jadwal)';
                            } else {
                                label.textContent = 'ON';
                            }
                        } else {
                            label.classList.remove('on');
                            label.textContent = 'OFF';
                        }
                    }
                });

                // Update bulk toggle button
                const bulkBtn = document.querySelector('[data-lamp-bulk-toggle]');
                if (bulkBtn) {
                    const lampDeviceNames = ['lampu1', 'lampu2', 'lampu3'];
                    const allManualOn = lampDeviceNames.every(device => Number(data.manual_controls[device]) === 1);
                    bulkBtn.dataset.lampBulkStatus = allManualOn ? '0' : '1';
                    
                    const bulkLabel = bulkBtn.querySelector('[data-lamp-bulk-label]');
                    const bulkIcon = bulkBtn.querySelector('[data-lamp-bulk-icon]');
                    if (bulkLabel) {
                        bulkLabel.textContent = allManualOn ? 'Matikan Semua Lampu' : 'Nyalakan Semua Lampu';
                    }
                    if (bulkIcon) {
                        if (allManualOn) {
                            bulkIcon.className = 'bi bi-lightbulb-off';
                        } else {
                            bulkIcon.className = 'bi bi-lightbulb-fill';
                        }
                    }
                    if (allManualOn) {
                        bulkBtn.classList.add('secondary');
                    } else {
                        bulkBtn.classList.remove('secondary');
                    }
                }
            }
        } catch (e) {
            console.error('Polling error:', e);
        }
    }
    
    // Initial poll and set interval
    setInterval(pollControls, 5000);
});
</script>
