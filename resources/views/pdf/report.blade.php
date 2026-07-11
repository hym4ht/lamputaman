<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        @page {
            margin: 1.5cm 1.5cm 1.5cm 1.5cm;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #2d3748;
            font-size: 11px;
            line-height: 1.5;
            margin: 0;
            padding: 0;
        }
        .header {
            border-bottom: 3px solid #6dab28;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            border: none;
        }
        .header-table td {
            border: none;
            padding: 0;
        }
        .logo-text {
            font-size: 22px;
            font-weight: bold;
            color: #2d3748;
            margin: 0;
            letter-spacing: -0.5px;
        }
        .logo-accent {
            color: #6dab28;
        }
        .report-title {
            font-size: 16px;
            font-weight: bold;
            color: #1a202c;
            margin: 5px 0 2px 0;
            text-transform: uppercase;
        }
        .report-subtitle {
            font-size: 11px;
            color: #718096;
            margin: 0;
        }
        .meta-text {
            text-align: right;
            font-size: 10px;
            color: #718096;
        }
        
        /* Stats Cards grid using tables */
        .stats-table {
            width: 100%;
            margin-bottom: 25px;
            border-collapse: separate;
            border-spacing: 10px 0;
            margin-left: -10px;
            margin-right: -10px;
        }
        .stats-table td {
            width: 25%;
            padding: 0;
            vertical-align: top;
        }
        .card {
            background-color: #f7fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px 10px;
            text-align: center;
        }
        .card-label {
            font-size: 9px;
            color: #718096;
            text-transform: uppercase;
            font-weight: bold;
            margin-bottom: 6px;
            letter-spacing: 0.5px;
        }
        .card-value {
            font-size: 18px;
            font-weight: bold;
            color: #1a202c;
            margin-bottom: 4px;
        }
        .card-value-small {
            font-size: 15px;
            font-weight: bold;
            color: #1a202c;
            margin-bottom: 4px;
        }
        .card-subtext {
            font-size: 8.5px;
            color: #a0aec0;
        }

        .section-title {
            font-size: 12px;
            font-weight: bold;
            color: #2d3748;
            border-left: 3px solid #6dab28;
            padding-left: 8px;
            margin-bottom: 12px;
            margin-top: 10px;
            text-transform: uppercase;
        }

        /* Data table */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }
        .data-table th {
            background-color: #6dab28;
            color: #ffffff;
            font-weight: bold;
            text-align: center;
            padding: 8px 10px;
            border: 1px solid #6dab28;
            font-size: 10px;
            text-transform: uppercase;
        }
        .data-table td {
            padding: 7px 10px;
            border: 1px solid #e2e8f0;
            text-align: center;
        }
        .data-table tr:nth-child(even) td {
            background-color: #fcfdfa;
        }
        .data-table tr:nth-child(even):hover td {
            background-color: #f7fafc;
        }

        /* Footer */
        .footer {
            position: fixed;
            bottom: -0.5cm;
            left: 0;
            right: 0;
            height: 20px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
            padding-top: 6px;
            color: #a0aec0;
            font-size: 8px;
        }
        
        .page-number:after {
            content: counter(page);
        }

        .alert-warning {
            background-color: #fffaf0;
            border: 1px solid #feebc8;
            color: #dd6b20;
            padding: 8px 12px;
            border-radius: 6px;
            margin-bottom: 15px;
            font-size: 10px;
        }
    </style>
</head>
<body>

    <div class="footer">
        Laporan Sensor Taman | Dicetak: {{ now()->timezone(config('app.timezone'))->format('d/m/Y H:i:s') }} | Halaman <span class="page-number"></span>
    </div>

    <div class="header">
        <table class="header-table">
            <tr>
                <td>
                    <div class="logo-text">GARDEN<span class="logo-accent">MONITORING</span></div>
                    <div class="report-title">{{ $title }}</div>
                    <div class="report-subtitle">Periode: {{ $periodLabel }}</div>
                </td>
                <td class="meta-text" style="vertical-align: bottom;">
                    <strong>Sistem IoT Lamputaman</strong><br>
                    Status Alat: Terkoneksi<br>
                    Jumlah Log: {{ number_format($totalCount) }} data
                </td>
            </tr>
        </table>
    </div>

    @if($sampled)
        <div class="alert-warning">
            <strong>Catatan:</strong> Data sensor disampling menjadi {{ number_format($renderedCount) }} titik dari total {{ number_format($totalCount) }} log data demi stabilitas performa dan efisiensi laporan PDF.
        </div>
    @endif

    <div class="section-title">Ringkasan Statistik</div>
    <table class="stats-table">
        <tr>
            <td>
                <div class="card">
                    <div class="card-label">Rata-Rata Suhu</div>
                    <div class="card-value">{{ number_format($avgSuhu, 1) }} &deg;C</div>
                    <div class="card-subtext">Suhu ideal: 24 - 32 &deg;C</div>
                </div>
            </td>
            <td>
                <div class="card">
                    <div class="card-label">Rata-Rata Lembab</div>
                    <div class="card-value">{{ number_format($avgKelembaban, 1) }} %</div>
                    <div class="card-subtext">Kelembaban normal</div>
                </div>
            </td>
            <td>
                <div class="card">
                    <div class="card-label">Min / Max Suhu</div>
                    <div class="card-value-small">{{ number_format($minSuhu, 1) }} / {{ number_format($maxSuhu, 1) }}</div>
                    <div class="card-subtext">Dalam derajat Celcius</div>
                </div>
            </td>
            <td>
                <div class="card">
                    <div class="card-label">Min / Max Lembab</div>
                    <div class="card-value-small">{{ number_format($minKelembaban, 1) }} / {{ number_format($maxKelembaban, 1) }}</div>
                    <div class="card-subtext">Dalam persen (%)</div>
                </div>
            </td>
        </tr>
    </table>

    <div class="section-title">Log Data Riwayat Sensor</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 10%;">No.</th>
                <th style="width: 35%;">Tanggal & Waktu</th>
                <th style="width: 27%;">Suhu (&deg;C)</th>
                <th style="width: 28%;">Kelembaban (%)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($readings as $index => $reading)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $reading->created_at_parsed ? $reading->created_at_parsed->timezone(config('app.timezone'))->format('d M Y H:i:s') : '-' }}</td>
                    <td style="font-weight: bold; color: #2d3748;">{{ number_format($reading->suhu, 1) }} &deg;C</td>
                    <td style="font-weight: bold; color: #2d3748;">{{ number_format($reading->kelembaban, 1) }} %</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="color: #a0aec0; padding: 20px;">Belum ada log data sensor dalam periode ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

</body>
</html>
