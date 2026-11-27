<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $masterOrder->letter_number }} - SPKL Bulanan</title>
    <style>
        @page { size: A4 portrait; margin: 18mm 18mm 16mm 18mm; }
        body { font-family: 'Times New Roman', Times, serif; color: #111827; font-size: 11.5pt; line-height: 1.5; margin: 0; padding: 16px; background: #fff; }
        .kop { display: flex; align-items: center; gap: 14px; border-bottom: 3px double #000; padding-bottom: 8px; margin-bottom: 16px; }
        .kop img { height: 64px; width: auto; }
        .kop-text { flex: 1; text-align: center; }
        .kop-text h1 { font-size: 12pt; font-weight: bold; margin: 0; text-transform: uppercase; }
        .kop-text h2 { font-size: 11pt; font-weight: bold; margin: 1px 0; text-transform: uppercase; }
        .kop-text p { font-size: 8.5pt; margin: 1px 0; }
        .title-block { text-align: center; margin: 18px 0; }
        .title-block h3 { font-size: 12pt; font-weight: bold; margin: 0; text-transform: uppercase; }
        .title-block p { font-size: 11pt; margin: 3px 0 0 0; font-weight: bold; }
        .signature-box { width: 260px; margin-left: auto; margin-top: 28px; page-break-inside: avoid; break-inside: avoid; }
        .page-break { page-break-before: always; break-before: page; }
        table.attachments { width: 100%; border-collapse: collapse; margin-top: 12px; font-size: 10pt; }
        table.attachments th, table.attachments td { border: 1px solid #000; padding: 6px 8px; vertical-align: top; }
        table.attachments th { background: #f3f4f6; text-align: center; }
        .no-print { text-align: right; margin-bottom: 12px; font-family: sans-serif; }
        .btn-print { background: #0058A8; color: #fff; border: none; padding: 8px 14px; border-radius: 8px; cursor: pointer; font-weight: bold; font-size: 12px; }
        @media print { .no-print { display: none; } body { padding: 0; } }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" class="btn-print">Cetak / Simpan PDF SPKL</button>
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
        <h3>SURAT PERINTAH KERJA LEMBUR NOMOR</h3>
        <p>{{ $masterOrder->letter_number }}</p>
    </div>

    <div style="text-align: justify; white-space: pre-line;">{{ $masterOrder->template_body }}</div>

    <div class="signature-box">
        <div>Bandar Lampung, {{ $masterOrder->letter_date?->locale('id')->translatedFormat('d F Y') }}</div>
        <div>{{ $masterOrder->signer_title }}</div>
        <div style="margin: 14px 0; font-size: 9pt; color: #4b5563;">
            {{ $masterOrder->isIssued() ? 'Ditandatangani secara elektronik' : '[Draf - Belum Terbit]' }}
        </div>
        <div style="font-weight: bold;">{{ $masterOrder->signer_name }}</div>
        @if($masterOrder->signer_nip)
            <div>NIP. {{ $masterOrder->signer_nip }}</div>
        @endif
    </div>

    <div class="page-break">
        <h3 style="text-align: center; text-transform: uppercase; margin-bottom: 4px;">Daftar Lampiran SPTPL Resmi</h3>
        <p style="text-align: center; margin-top: 0; font-size: 10.5pt;">
            Lampiran {{ $masterOrder->letter_number }} &bull; Periode {{ $masterOrder->getPeriodLabel() }}
        </p>

        <table class="attachments">
            <thead>
                <tr>
                    <th style="width: 34px;">No</th>
                    <th style="width: 185px;">Nomor SPTPL</th>
                    <th style="width: 120px;">Tanggal Lembur</th>
                    <th>Daftar Pegawai, Waktu &amp; Uraian Kegiatan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($attachments as $idx => $sptpl)
                    <tr>
                        <td style="text-align: center;">{{ $idx + 1 }}</td>
                        <td style="font-weight: bold;">{{ $sptpl->letter_number }}</td>
                        <td style="text-align: center;">{{ $sptpl->getDateRangeLabel() }}</td>
                        <td>
                            @foreach($sptpl->items as $item)
                                <div>
                                    &bull; <strong>{{ $item->employee?->full_name }}</strong>
                                    ({{ $item->start_time }}-{{ $item->end_time }}, {{ $item->getFormattedDuration() }}):
                                    {{ $item->description }}
                                </div>
                            @endforeach
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="text-align: center; color: #6b7280; padding: 16px;">
                            Belum ada SPTPL berstatus Disetujui pada bulan ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</body>
</html>
