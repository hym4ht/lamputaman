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
