<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Berita Acara Pembinaan Disiplin - {{ $employee->full_name }} ({{ $employee->employee_code }})</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 15mm 15mm 15mm 15mm;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            color: #111827;
            background-color: #f3f4f6;
            margin: 0;
            padding: 20px;
            font-size: 12px;
            line-height: 1.4;
        }

        .paper {
            max-width: 210mm;
            margin: 0 auto;
            background: #ffffff;
            padding: 20mm;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            border-radius: 4px;
        }

        .header-table {
            width: 100%;
            border-bottom: 2px solid #000;
            padding-bottom: 8px;
            margin-bottom: 16px;
        }

        .header-title {
            text-align: center;
            font-family: Arial, Helvetica, sans-serif;
            line-height: 1.25;
        }

        .header-title h4 {
            margin: 0;
            font-size: 11pt;
            font-weight: normal;
            letter-spacing: 0.5px;
        }

        .header-title h3 {
            margin: 2px 0;
            font-size: 12pt;
            font-weight: bold;
            letter-spacing: 0.5px;
        }

        .header-title h2 {
            margin: 2px 0;
            font-size: 13pt;
            font-weight: bold;
            letter-spacing: 0.5px;
        }

        .header-title p {
            margin: 2px 0 0 0;
            font-size: 8.5pt;
            color: #4b5563;
        }

        .doc-title {
            text-align: center;
            margin: 16px 0;
            text-transform: uppercase;
        }

        .doc-title h2 {
            font-size: 13pt;
            margin: 0;
            text-decoration: underline;
            font-weight: bold;
        }

        .doc-title p {
            font-size: 10pt;
            margin: 3px 0 0 0;
            font-family: Arial, Helvetica, sans-serif;
            color: #374151;
        }

        .alert-box {
            padding: 10px 14px;
            border-radius: 6px;
            margin-bottom: 16px;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10pt;
        }

        .alert-danger {
            background-color: #fef2f2;
            border: 1.5px solid #dc2626;
            color: #991b1b;
        }

        .alert-warning {
            background-color: #fffbeb;
            border: 1.5px solid #d97706;
            color: #92400e;
        }

        .alert-success {
            background-color: #f0fdf4;
            border: 1.5px solid #16a34a;
            color: #166534;
        }

        .section-heading {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10pt;
            font-weight: bold;
            text-transform: uppercase;
            border-bottom: 1px solid #9ca3af;
            padding-bottom: 3px;
            margin: 14px 0 8px 0;
            color: #1f2937;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            font-size: 10pt;
        }

        .info-table td {
            padding: 3px 4px;
            vertical-align: top;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
            font-size: 9.5pt;
        }

        .data-table th, .data-table td {
            border: 1px solid #4b5563;
            padding: 5px 6px;
        }

        .data-table th {
            background-color: #f3f4f6;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
            font-size: 8.5pt;
            font-family: Arial, Helvetica, sans-serif;
        }

        .signatures {
            width: 100%;
            margin-top: 25px;
            page-break-inside: avoid;
        }

        .signatures td {
            text-align: center;
            vertical-align: top;
            width: 33.33%;
            padding: 0 10px;
            font-size: 10pt;
        }

        .sign-space {
            height: 60px;
        }

        .no-print-bar {
            max-width: 210mm;
            margin: 0 auto 15px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #ffffff;
            padding: 12px 18px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            font-family: Arial, Helvetica, sans-serif;
        }

        .btn {
            display: inline-block;
            padding: 7px 15px;
            font-size: 12px;
            font-weight: bold;
            border-radius: 6px;
            text-decoration: none;
            cursor: pointer;
            border: none;
        }

        .btn-primary {
            background-color: #0058A8;
            color: #ffffff;
        }

        .btn-primary:hover {
            background-color: #003D75;
        }

        .btn-secondary {
            background-color: #e5e7eb;
            color: #374151;
            margin-right: 8px;
        }

        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }

            .paper {
                box-shadow: none;
                padding: 0;
                margin: 0;
            }

            .no-print-bar {
                display: none !important;
            }
        }
    </style>
</head>
<body>

    <!-- Action Toolbar (Hidden during print) -->
    <div class="no-print-bar">
        <div>
            <strong style="color: #0058A8;">Berita Acara Pembinaan Disiplin PPNPN</strong>
            <span style="color: #6b7280; font-size: 11px; margin-left: 8px;">FR-407 • Early Warning System</span>
        </div>
        <div>
            <button onclick="window.history.back()" class="btn btn-secondary">Kembali</button>
            <button onclick="window.print()" class="btn btn-primary">Cetak / Simpan PDF</button>
        </div>
    </div>

    <div class="paper">
        <!-- Formal Kop Surat -->
        <table class="header-table">
            <tr>
                <td class="header-title">
                    <h4>KEMENTERIAN KEUANGAN REPUBLIK INDONESIA</h4>
                    <h3>DIREKTORAT JENDERAL PERBENDAHARAAN</h3>
                    <h2>KANTOR WILAYAH DIREKTORAT JENDERAL PERBENDAHARAAN PROVINSI LAMPUNG</h2>
                    <p>Jalan Gatot Subroto No. 34, Bandar Lampung 35226 &bull; Telepon: (0721) 481745</p>
                </td>
            </tr>
        </table>

        <!-- Document Heading -->
        <div class="doc-title">
            <h2>BERITA ACARA PEMBINAAN DISIPLIN PPNPN</h2>
            <p>Nomor: BAP-DIS/WPB.08/PPK/{{ $generatedAt->year }}/{{ str_pad((string)$employee->id, 4, '0', STR_PAD_LEFT) }}</p>
        </div>

        <!-- EWS Visual Warning Alert -->
        <div class="alert-box {{ $ewsStatus['is_critical'] ? 'alert-danger' : ($ewsStatus['is_warning'] ? 'alert-warning' : 'alert-success') }}">
            <table style="width: 100%;">
                <tr>
                    <td style="width: 40px; vertical-align: top; font-size: 20px;">
                        @if($ewsStatus['is_critical'])
                            🚨
                        @elseif($ewsStatus['is_warning'])
                            ⚠️
                        @else
                            🛡️
                        @endif
                    </td>
                    <td>
                        <strong style="font-size: 11pt; text-transform: uppercase;">
                            {{ $ewsStatus['label'] }} &bull; Saldo Aktif: {{ $activePoints }} Poin
                        </strong>
                        <div style="margin-top: 3px; font-size: 9.5pt;">
                            {{ $ewsStatus['description'] }}
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Section 1: Identitas Pegawai -->
        <div class="section-heading">I. IDENTITAS PEGAWAI PEMERINTAH NON PEGAWAI NEGERI (PPNPN)</div>
        <table class="info-table">
            <tr>
                <td style="width: 25%;">Nama Lengkap</td>
                <td style="width: 2%;">:</td>
                <td style="width: 73%; font-weight: bold;">{{ $employee->full_name }}</td>
            </tr>
            <tr>
                <td>Nomor Induk Pegawai / Kode</td>
                <td>:</td>
                <td>{{ $employee->employee_code }}</td>
            </tr>
            <tr>
                <td>Posisi Tugas / Pekerjaan</td>
                <td>:</td>
                <td>{{ $employee->position?->name ?? 'PPNPN' }}</td>
            </tr>
            <tr>
                <td>Surat Perintah Kerja (SPK)</td>
                <td>:</td>
                <td>Nomor: {{ $employee->spk_number ?? 'SPK-20/WPB.08/PPK/2025' }}</td>
            </tr>
            <tr>
                <td>Masa Berlaku Kontrak</td>
                <td>:</td>
                <td>
                    {{ $employee->contract_start_date?->translatedFormat('d F Y') ?? '-' }} s.d. 
                    {{ $employee->contract_end_date?->translatedFormat('d F Y') ?? '-' }}
                </td>
            </tr>
            <tr>
                <td>Supervisor Lapangan</td>
                <td>:</td>
                <td>{{ $employee->supervisor?->name ?? '-' }}</td>
            </tr>
            <tr>
                <td>Pejabat Pembuat Komitmen (PPK)</td>
                <td>:</td>
                <td>{{ $employee->atasan?->name ?? 'Kasubbag Umum / PPK Kanwil DJPb Lampung' }}</td>
            </tr>
        </table>

        <!-- Section 2: Ringkasan Rekam Jejak Pelanggaran -->
        <div class="section-heading">II. REKAM JEJAK PELANGGARAN AKTIF (LAMPIRAN II SPK)</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 5%;">No</th>
                    <th style="width: 15%;">Tgl Kejadian</th>
                    <th style="width: 20%;">Butir SPK</th>
                    <th style="width: 35%;">Uraian Pelanggaran</th>
                    <th style="width: 10%;">Poin</th>
                    <th style="width: 15%;">Pencatat</th>
                </tr>
            </thead>
            <tbody>
                @forelse($violations as $index => $v)
                    <tr>
                        <td style="text-align: center;">{{ $index + 1 }}</td>
                        <td style="text-align: center;">{{ $v->incident_date?->format('d/m/Y') ?? '-' }}</td>
                        <td><strong>{{ $v->catalog?->clause_number ?? 'SPK' }}</strong></td>
                        <td>{{ $v->notes ?? $v->catalog?->name ?? '-' }}</td>
                        <td style="text-align: center; font-weight: bold; color: #dc2626;">+{{ $v->points }}</td>
                        <td>{{ $v->supervisor?->name ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; color: #6b7280; padding: 12px;">
                            Tidak ada catatan pelanggaran aktif pada periode berjalan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr style="background-color: #f9fafb; font-weight: bold;">
                    <td colspan="4" style="text-align: right; padding-right: 10px;">TOTAL SALDO POIN DISIPLIN AKTIF:</td>
                    <td style="text-align: center; color: #dc2626; font-size: 10pt;">{{ $activePoints }} Poin</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>

        <!-- Section 3: Riwayat Surat Peringatan (SP) -->
        <div class="section-heading">III. STATUS SURAT PERINGATAN (SP) & PEMOTONGAN HONOR (LAMPIRAN III SPK)</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 5%;">No</th>
                    <th style="width: 25%;">Tingkat Sanksi</th>
                    <th style="width: 15%;">Tgl Terbit</th>
                    <th style="width: 20%;">Potongan Honor</th>
                    <th style="width: 20%;">Jadwal Masa Potong</th>
                    <th style="width: 15%;">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sanctions as $index => $s)
                    <tr>
                        <td style="text-align: center;">{{ $index + 1 }}</td>
                        <td><strong>{{ $s->getTypeLabel() }}</strong></td>
                        <td style="text-align: center;">{{ $s->issued_date?->format('d/m/Y') ?? '-' }}</td>
                        <td style="text-align: center;">
                            @if($s->is_termination_recommended)
                                <span style="color: #dc2626; font-weight: bold;">Putus Kontrak (SP 3)</span>
                            @elseif($s->deduction_percentage > 0)
                                {{ $s->deduction_percentage }}% ({{ $s->duration_months }} Bulan)
                            @else
                                -
                            @endif
                        </td>
                        <td style="text-align: center;">
                            @if($s->start_date && $s->end_date)
                                {{ $s->start_date->format('M Y') }} s.d. {{ $s->end_date->format('M Y') }}
                            @else
                                -
                            @endif
                        </td>
                        <td style="text-align: center;">
                            @if($s->is_active)
                                <span style="font-weight: bold; color: #dc2626;">BERJALAN</span>
                            @else
                                <span style="color: #6b7280;">SELESAI</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; color: #6b7280; padding: 12px;">
                            Belum pernah diterbitkan Surat Peringatan (SP).
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Section 4: Catatan Pembinaan & Kesepakatan Tindak Lanjut -->
        <div class="section-heading">IV. ARAHAN PEMBINAAN & TINDAK LANJUT</div>
        <div style="border: 1px solid #4b5563; min-height: 80px; padding: 8px; font-size: 9.5pt; margin-bottom: 16px;">
            <p style="margin: 0; color: #4b5563; font-style: italic;">
                Catatan evaluasi, konseling, atau komitmen perbaikan kinerja personil:
            </p>
        </div>

        <p style="font-size: 9pt; color: #4b5563; margin-bottom: 20px;">
            Demikian Berita Acara Pembinaan Disiplin ini dibuat dengan sebenarnya untuk diketahui, ditaati, dan menjadi bagian tidak terpisahkan dari evaluasi masa perpanjangan kontrak kerja PPNPN pada Kantor Wilayah Direktorat Jenderal Perbendaharaan Provinsi Lampung.
        </p>

        <!-- Lembar Pengesahan Tanda Tangan -->
        <table class="signatures">
            <tr>
                <td>
                    PPNPN yang Bersangkutan,
                    <div class="sign-space"></div>
                    <strong><u>{{ $employee->full_name }}</u></strong><br>
                    <span>NIP: {{ $employee->employee_code }}</span>
                </td>
                <td>
                    Supervisor Lapangan,
                    <div class="sign-space"></div>
                    <strong><u>{{ $employee->supervisor?->name ?? '........................................' }}</u></strong><br>
                    <span>Pengawas Lapangan</span>
                </td>
                <td>
                    Bandar Lampung, {{ $generatedAt->translatedFormat('d F Y') }}<br>
                    Pejabat Pembuat Komitmen,
                    <div class="sign-space"></div>
                    <strong><u>{{ $employee->atasan?->name ?? '........................................' }}</u></strong><br>
                    <span>NIP. ........................................</span>
                </td>
            </tr>
        </table>
    </div>

</body>
</html>
