<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $overtimeRequest->getDisplayCode() }} - SPTPL</title>
    <style>
        @page { size: A4 portrait; margin: 18mm 18mm 16mm 18mm; }
        body { font-family: 'Times New Roman', Times, serif; color: #111827; font-size: 11.5pt; line-height: 1.45; margin: 0; padding: 16px; background: #fff; }
        .kop { display: flex; align-items: center; gap: 14px; border-bottom: 3px double #000; padding-bottom: 8px; margin-bottom: 14px; }
        .kop img { height: 64px; width: auto; }
        .kop-text { flex: 1; text-align: center; }
        .kop-text h1 { font-size: 12pt; font-weight: bold; margin: 0; text-transform: uppercase; }
        .kop-text h2 { font-size: 11pt; font-weight: bold; margin: 1px 0; text-transform: uppercase; }
        .kop-text p { font-size: 8.5pt; margin: 1px 0; }
        .title-block { text-align: center; margin: 14px 0; }
        .title-block h3 { font-size: 12pt; font-weight: bold; text-decoration: underline; margin: 0; text-transform: uppercase; }
        .title-block p { font-size: 11pt; margin: 3px 0 0 0; font-weight: bold; }
        table.items { width: 100%; border-collapse: collapse; margin: 12px 0; font-size: 10.5pt; }
        table.items th, table.items td { border: 1px solid #000; padding: 6px 8px; vertical-align: top; }
        table.items th { background: #f3f4f6; text-align: center; font-weight: bold; }
        table.items thead { display: table-header-group; }
        table.items tr { page-break-inside: avoid; break-inside: avoid; }
        .closing-and-signature { page-break-inside: avoid; break-inside: avoid; margin-top: 12px; }
        .signature-box { width: 260px; margin-left: auto; margin-top: 18px; }
        .qr-placeholder { border: 1px solid #9ca3af; width: 64px; height: 64px; margin: 8px 0; display: flex; align-items: center; justify-content: center; font-size: 8pt; color: #4b5563; text-align: center; }
        .no-print { text-align: right; margin-bottom: 12px; font-family: sans-serif; }
        .btn-print { background: #0058A8; color: #fff; border: none; padding: 8px 14px; border-radius: 8px; cursor: pointer; font-weight: bold; font-size: 12px; }
        @media print { .no-print { display: none; } body { padding: 0; } }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" class="btn-print">Cetak / Simpan PDF</button>
    </div>

    <div class="kop">
        <img src="{{ asset('images/logo-djpb.png') }}" alt="Logo Kemenkeu">
        <div class="kop-text">
            <h1>Kementerian Keuangan Republik Indonesia</h1>
            <h1>Direktorat Jenderal Perbendaharaan</h1>
            <h2>{{ $settings->satker_name }}</h2>
            <p>Jl. Cut Mutia Nomor 23 A, Gulak Galik, Bandar Lampung - 35214 &bull; Telepon: (0721) 487423</p>
        </div>
    </div>

    <div class="title-block">
        <h3>Surat Pernyataan Tanggung Jawab Perintah Lembur</h3>
        <p>NOMOR: {{ $overtimeRequest->getDisplayCode() }}</p>
    </div>

    <p style="text-align: justify;">
        Sehubungan dengan adanya kegiatan lembur yang akan dilaksanakan di lingkungan
        {{ $settings->satker_name }} dengan ini kami memerintahkan Pegawai Pemerintah Non Pegawai Negeri (PPNPN) berikut:
    </p>

    <table class="items">
        <thead>
            <tr>
                <th style="width: 36px;">No</th>
                <th style="width: 155px;">Nama</th>
                <th style="width: 110px;">Waktu Penugasan</th>
                <th style="width: 115px;">Waktu &amp; Durasi</th>
                <th>Uraian Kegiatan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($overtimeRequest->items as $idx => $item)
                <tr>
                    <td style="text-align: center;">{{ $idx + 1 }}.</td>
                    <td>{{ $item->employee?->full_name ?? '-' }}</td>
                    <td style="text-align: center;">{{ $overtimeRequest->getDateRangeLabel() }}</td>
                    <td style="text-align: center;">
                        {{ $item->start_time }} - {{ $item->end_time }}<br>
                        <strong>({{ $item->getFormattedDuration() }})</strong>
                    </td>
                    <td>{{ $item->description }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="closing-and-signature">
        <p style="text-align: justify;">
            Untuk menyelesaikan pekerjaan tersebut pada tanggal {{ $overtimeRequest->getDateRangeLabel() }}.<br>
            Demikian surat perintah ini dibuat dengan sebenarnya apabila di kemudian hari ternyata Surat Perintah ini tidak benar, saya bersedia dikenakan sanksi sesuai peraturan perundang-undangan.
        </p>

        <div class="signature-box">
            <div>Bandar Lampung, {{ $overtimeRequest->letter_date?->locale('id')->translatedFormat('d F Y') }}</div>
            <div>{{ $overtimeRequest->signer_title }}</div>

            @if($overtimeRequest->isApproved())
                <div class="qr-placeholder">TTE Resmi<br>{{ $overtimeRequest->approved_at?->format('d/m/Y') }}</div>
                <div style="font-size: 9pt; color: #6b7280;">Ditandatangani secara elektronik</div>
            @else
                <div style="height: 54px;"></div>
                <div style="font-size: 9pt; color: #b45309;">[Status: {{ $overtimeRequest->getStatusLabel() }}]</div>
            @endif

            <div style="font-weight: bold; margin-top: 4px;">{{ $overtimeRequest->signer_name }}</div>
            @if($overtimeRequest->signer_nip)
                <div>NIP. {{ $overtimeRequest->signer_nip }}</div>
            @endif
        </div>
    </div>
</body>
</html>
