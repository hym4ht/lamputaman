<!-- Schedule Section -->
<section class="schedule-panel">
    <div class="section-header">
        <div class="section-label">Pengaturan Jadwal</div>
        <h2 class="section-title">Lampu & Pompa</h2>
    </div>

    <div class="schedule-mobile-tabs" role="tablist" aria-label="Pilihan jadwal">
        <button class="schedule-mobile-tab active"
                type="button"
                data-schedule-tab="lampu"
                aria-pressed="true">
            Lampu
        </button>
        <button class="schedule-mobile-tab"
                type="button"
                data-schedule-tab="pompa"
                aria-pressed="false">
            Pompa
        </button>
    </div>

    <div class="schedule-grid">
        <div class="schedule-form-stack">
            <div class="schedule-form active" data-schedule-section="lampu">
                <div class="section-header">
                    <div class="section-label">Alarm Lampu</div>
                    <h2 class="section-title">Jadwal Lampu</h2>
                </div>

                <form method="POST" action="{{ route('dashboard.lamp-schedules.store') }}">
                    @csrf

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="lampStartTime">Jam Nyala</label>
                            <input class="form-input"
                                   id="lampStartTime"
                                   name="start_time"
                                   type="time"
                                   value="{{ old('start_time', '18:00') }}"
                                   required>
                            @error('start_time')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="lampEndTime">Jam Mati</label>
                            <input class="form-input"
                                   id="lampEndTime"
                                   name="end_time"
                                   type="time"
                                   value="{{ old('end_time', '06:00') }}"
                                   required>
                            @error('end_time')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <button class="btn-primary" type="submit">Tambah Jadwal Lampu</button>
                </form>
            </div>

            <div class="schedule-form" data-schedule-section="pompa">
                <div class="section-header">
                    <div class="section-label">Alarm Pompa</div>
                    <h2 class="section-title">Jadwal Pompa</h2>
                </div>

                <form method="POST" action="{{ route('dashboard.pump-schedules.store') }}">
                    @csrf
                    <input type="hidden" name="is_enabled" value="0">

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="pumpScheduleName">Nama</label>
                            <input class="form-input"
                                   id="pumpScheduleName"
                                   name="name"
                                   type="text"
                                   maxlength="80"
                                   value="{{ old('name') }}"
                                   placeholder="Pagi">
                            @error('name')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="pumpStartTime">Jam Mulai</label>
                            <input class="form-input"
                                   id="pumpStartTime"
                                   name="start_time"
                                   type="time"
                                   value="{{ old('start_time', '06:00') }}"
                                   required>
                            @error('start_time')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="pumpDurationMinutes">Durasi (menit)</label>
                            <input class="form-input"
                                   id="pumpDurationMinutes"
                                   name="duration_minutes"
                                   type="number"
                                   min="1"
                                   max="1440"
                                   value="{{ old('duration_minutes', 10) }}"
                                   required>
                            @error('duration_minutes')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="form-group schedule-days-group">
                        <label class="form-label">Pilih Hari</label>
                        <label class="every-day-option" for="pumpEveryDayToggle">
                            <input type="checkbox"
                                   class="every-day-toggle"
                                   id="pumpEveryDayToggle"
                                   @checked(collect(old('days', array_keys($dayLabels)))->count() === count($dayLabels))>
                            <span>Setiap hari</span>
                        </label>
                        <div class="day-selector">
                            @foreach ($dayLabels as $day => $label)
                                <div class="day-checkbox">
                                    <input type="checkbox"
                                           class="schedule-day"
                                           name="days[]"
                                           id="pump-day-{{ $day }}"
                                           value="{{ $day }}"
                                           @checked(collect(old('days', array_keys($dayLabels)))->contains((string) $day) || collect(old('days', array_keys($dayLabels)))->contains($day))>
                                    <label class="day-label" for="pump-day-{{ $day }}">
                                        {{ substr($label, 0, 3) }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                        @error('days')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="checkbox"
                                   id="pumpScheduleEnabled"
                                   name="is_enabled"
                                   value="1"
                                   checked
                                   style="width: 16px; height: 16px; cursor: pointer;">
                            <span class="form-label" style="margin: 0; font-size: 14px;">Aktifkan Langsung</span>
                        </label>
                    </div>

                    <button class="btn-primary" type="submit">Tambah Jadwal Pompa</button>
                </form>
            </div>
        </div>

        <div class="schedule-list-stack">
            <div class="schedule-list active" data-schedule-section="lampu">
                <div class="section-header">
                    <div class="section-label">Daftar Alarm</div>
                    <h2 class="section-title">Jadwal Lampu</h2>
                </div>

                <div class="schedule-items">
                    @forelse ($lampSchedules as $schedule)
                        <div class="schedule-item">
                            <div class="schedule-details">
                                <h4>{{ $schedule->name }}</h4>
                                <div class="schedule-meta">
                                    {{ $schedule->targetLabel() }} · {{ $schedule->daysLabel() }} · {{ $schedule->timeRangeLabel() }}
                                </div>
                            </div>

                            <div class="schedule-actions">
                                <span class="badge {{ $schedule->is_enabled ? 'active' : 'inactive' }}">
                                    {{ $schedule->is_enabled ? 'Aktif' : 'Mati' }}
                                </span>

                                <form method="POST" action="{{ route('dashboard.lamp-schedules.toggle', $schedule) }}" style="display: inline;">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="is_enabled" value="{{ $schedule->is_enabled ? 0 : 1 }}">
                                    <button class="btn-sm" type="submit">
                                        {{ $schedule->is_enabled ? 'Matikan' : 'Aktifkan' }}
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('dashboard.lamp-schedules.destroy', $schedule) }}" style="display: inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn-sm btn-danger" type="submit">Hapus</button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="empty-state">
                            <i class="bi bi-calendar-x"></i>
                            <p>Belum ada jadwal lampu.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="schedule-list" data-schedule-section="pompa">
                <div class="section-header">
                    <div class="section-label">Daftar Alarm</div>
                    <h2 class="section-title">Jadwal Pompa</h2>
                </div>

                <div class="schedule-items">
                    @forelse ($pumpSchedules as $schedule)
                        <div class="schedule-item">
                            <div class="schedule-details">
                                <h4>{{ $schedule->name }}</h4>
                                <div class="schedule-meta">
                                    {{ $schedule->daysLabel() }} · {{ $schedule->startsAtLabel() }} · {{ $schedule->duration_minutes }} menit
                                </div>
                            </div>

                            <div class="schedule-actions">
                                <span class="badge {{ $schedule->is_enabled ? 'active' : 'inactive' }}">
                                    {{ $schedule->is_enabled ? 'Aktif' : 'Mati' }}
                                </span>

                                <form method="POST" action="{{ route('dashboard.pump-schedules.toggle', $schedule) }}" style="display: inline;">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="is_enabled" value="{{ $schedule->is_enabled ? 0 : 1 }}">
                                    <button class="btn-sm" type="submit">
                                        {{ $schedule->is_enabled ? 'Matikan' : 'Aktifkan' }}
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('dashboard.pump-schedules.destroy', $schedule) }}" style="display: inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn-sm btn-danger" type="submit">Hapus</button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="empty-state">
                            <i class="bi bi-calendar-x"></i>
                            <p>Belum ada jadwal pompa.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</section>
