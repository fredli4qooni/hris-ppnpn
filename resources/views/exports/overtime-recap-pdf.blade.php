<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Lembur PPNPN {{ $recap['month_name'] }} {{ $recap['year'] }}</title>
    <style>
        @page { size: A4 landscape; margin: 10mm; }
        body { font-family: Arial, sans-serif; font-size: 9px; color: #111827; margin: 0; padding: 12px; }
        h2, p { margin: 2px 0; text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #374151; padding: 3px 4px; text-align: center; }
        th { background: #f3f4f6; }
        .left { text-align: left; }
        .hol { background: #fee2e2; }
        .no-print { text-align: right; margin-bottom: 8px; }
        @media print { .no-print { display: none; } body { padding: 0; } }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" style="padding: 6px 12px; background: #0058A8; color: #fff; border: none; border-radius: 6px; cursor: pointer;">Cetak / PDF</button>
    </div>
    <h2>REKAPITULASI JAM LEMBUR PPNPN - {{ strtoupper($recap['month_name']) }} {{ $recap['year'] }}</h2>
    <p>{{ $settings->satker_name }}</p>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th class="left">Nama Pegawai</th>
                @for($d = 1; $d <= $recap['days_in_month']; $d++)
                    <th class="{{ isset($recap['holidays'][$d]) ? 'hol' : '' }}">{{ $d }}</th>
                @endfor
                <th>Kerja</th>
                <th>Libur</th>
                <th>Surat</th>
                <th>Tanpa Surat</th>
                <th>Total</th>
                <th class="left">Keterangan Surat</th>
            </tr>
        </thead>
        <tbody>
            @php $no = 1; @endphp
            @foreach($recap['items'] as $row)
                <tr>
                    <td>{{ $no++ }}</td>
                    <td class="left"><strong>{{ $row['name'] }}</strong></td>
                    @for($d = 1; $d <= $recap['days_in_month']; $d++)
                        @php $hrs = (float) $row['days'][$d]['total_hours']; @endphp
                        <td class="{{ isset($recap['holidays'][$d]) ? 'hol' : '' }}">{{ $hrs > 0 ? $hrs : '' }}</td>
                    @endfor
                    <td>{{ (float) $row['workday_hours'] }}</td>
                    <td>{{ (float) $row['holiday_hours'] }}</td>
                    <td>{{ (float) $row['hours_with_letter'] }}</td>
                    <td>{{ (float) $row['hours_without_letter'] }}</td>
                    <td><strong>{{ (float) $row['total_hours'] }}</strong></td>
                    <td class="left">{{ $row['overtime_status_label'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
