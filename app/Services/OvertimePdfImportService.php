<?php

namespace App\Services;

use Carbon\Carbon;

class OvertimePdfImportService
{
    /**
     * Indonesian month names mapped to month numbers.
     *
     * @var array<string, int>
     */
    private const MONTH_MAP = [
        'januari' => 1,
        'jan' => 1,
        'februari' => 2,
        'feb' => 2,
        'maret' => 3,
        'mar' => 3,
        'april' => 4,
        'apr' => 4,
        'mei' => 5,
        'may' => 5,
        'juni' => 6,
        'jun' => 6,
        'juli' => 7,
        'jul' => 7,
        'agustus' => 8,
        'agu' => 8,
        'aug' => 8,
        'september' => 9,
        'sep' => 9,
        'sept' => 9,
        'oktober' => 10,
        'okt' => 10,
        'oct' => 10,
        'november' => 11,
        'nov' => 11,
        'desember' => 12,
        'des' => 12,
        'dec' => 12,
    ];

    /**
     * Parse an uploaded PDF binary and extract date(s), time window, and description.
     *
     * @return array{
     *     start_date: ?string,
     *     end_date: ?string,
     *     start_time: ?string,
     *     end_time: ?string,
     *     description: ?string,
     *     raw_text_excerpt: string,
     *     has_extractable_text: bool
     * }
     */
    public function parsePdfBinary(string $rawBinary): array
    {
        $text = $this->extractTextFromPdfBinary($rawBinary);
        $normalized = trim(preg_replace('/[ \t]+/', ' ', str_replace(["\r\n", "\r"], "\n", $text)) ?? '');

        [$startDate, $endDate] = $this->extractDates($normalized);
        [$startTime, $endTime] = $this->extractTimes($normalized);
        $description = $this->extractDescription($normalized);

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'description' => $description,
            'raw_text_excerpt' => mb_substr($normalized, 0, 1000),
            'has_extractable_text' => $normalized !== '',
        ];
    }

    /**
     * Extract text operators from uncompressed and FlateDecode PDF streams.
     */
    public function extractTextFromPdfBinary(string $rawBinary): string
    {
        if (! str_starts_with(ltrim($rawBinary), '%PDF')) {
            return '';
        }

        $chunks = [];

        if (preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $rawBinary, $matches)) {
            foreach ($matches[1] as $streamData) {
                $decoded = $this->tryDecompress($streamData);
                $extracted = $this->extractPdfTextOperators($decoded);
                if ($extracted !== '') {
                    $chunks[] = $extracted;
                }
            }
        }

        if ($chunks === []) {
            $fallback = $this->extractPdfTextOperators($rawBinary);
            if ($fallback !== '') {
                $chunks[] = $fallback;
            }
        }

        return implode("\n", $chunks);
    }

    /**
     * Attempt zlib inflation/uncompression cleanly.
     */
    private function tryDecompress(string $stream): string
    {
        $uncompressed = @gzuncompress($stream);
        if ($uncompressed !== false) {
            return $uncompressed;
        }

        $inflated = @gzinflate($stream);
        if ($inflated !== false) {
            return $inflated;
        }

        return $stream;
    }

    /**
     * Extract text from PDF Tj / TJ text-showing operators.
     */
    private function extractPdfTextOperators(string $content): string
    {
        $lines = [];

        if (preg_match_all('/BT\b(.*?)ET\b/s', $content, $btBlocks)) {
            foreach ($btBlocks[1] as $block) {
                if (preg_match_all('/\((?:\\\\.|[^\\\\()])*\)\s*Tj|\[(.*?)\]\s*TJ|\b(?:Td|TD|T\*|Tm)\b/s', $block, $ops, PREG_SET_ORDER)) {
                    $lineBuffer = [];
                    foreach ($ops as $op) {
                        $token = trim($op[0]);
                        if (in_array($token, ['Td', 'TD', 'T*', 'Tm'], true)) {
                            $joined = trim(implode('', $lineBuffer));
                            if ($joined !== '') {
                                $lines[] = $joined;
                                $lineBuffer = [];
                            }

                            continue;
                        }

                        if (isset($op[1]) && $op[1] !== '') {
                            if (preg_match_all('/\((?:\\\\.|[^\\\\()])*\)/', $op[1], $innerStrings)) {
                                foreach ($innerStrings[0] as $literal) {
                                    $lineBuffer[] = $this->decodePdfLiteral($literal);
                                }
                            }
                        } else {
                            $lineBuffer[] = $this->decodePdfLiteral($op[0]);
                        }
                    }

                    $joined = trim(implode('', $lineBuffer));
                    if ($joined !== '') {
                        $lines[] = $joined;
                    }
                }
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Unescape a PDF literal string like (Tanggal\: 27 November 2026)Tj.
     */
    private function decodePdfLiteral(string $literal): string
    {
        if (preg_match('/^\((.*)\)\s*(?:Tj)?$/s', trim($literal), $m)) {
            $literal = $m[1];
        }

        return strtr($literal, [
            '\\n' => "\n",
            '\\r' => "\r",
            '\\t' => "\t",
            '\\(' => '(',
            '\\)' => ')',
            '\\\\' => '\\',
        ]);
    }

    /**
     * Extract start_date and end_date from PDF text.
     *
     * @return array{0: ?string, 1: ?string}
     */
    private function extractDates(string $text): array
    {
        if ($text === '') {
            return [null, null];
        }

        $foundDates = [];

        // Match full Indonesian date range: "28 November 2026 s.d. 29 November 2026"
        $monthPattern = implode('|', array_keys(self::MONTH_MAP));
        if (preg_match('/(?<!\d)(\d{1,2})\s+('.$monthPattern.')\s+(\d{4})\s*(?:-|–|—|s\.?d\.?|sampai|hingg?a)\s*(\d{1,2})\s+('.$monthPattern.')\s+(\d{4})(?!\d)/i', $text, $fullRangeMatch)) {
            $m1 = self::MONTH_MAP[strtolower($fullRangeMatch[2])] ?? null;
            $m2 = self::MONTH_MAP[strtolower($fullRangeMatch[5])] ?? null;
            if ($m1 !== null && $m2 !== null) {
                $start = $this->safeCreateDate((int) $fullRangeMatch[3], $m1, (int) $fullRangeMatch[1]);
                $end = $this->safeCreateDate((int) $fullRangeMatch[6], $m2, (int) $fullRangeMatch[4]);
                if ($start && $end && $end >= $start) {
                    return [$start, $end];
                }
            }
        }

        // Match Indonesian date range with same month: "24 s.d. 25 Oktober 2026" or "24-25 Oktober 2026"
        if (preg_match('/(?<!\d)(\d{1,2})\s*(?:-|–|—|s\.?d\.?|sampai)\s*(\d{1,2})\s+('.$monthPattern.')\s+(\d{4})(?!\d)/i', $text, $rangeMatch)) {
            $month = self::MONTH_MAP[strtolower($rangeMatch[3])] ?? null;
            $year = (int) $rangeMatch[4];
            if ($month !== null) {
                $start = $this->safeCreateDate($year, $month, (int) $rangeMatch[1]);
                $end = $this->safeCreateDate($year, $month, (int) $rangeMatch[2]);
                if ($start && $end && $end >= $start) {
                    return [$start, $end];
                }
            }
        }

        // Match numeric dates: DD-MM-YYYY or DD/MM/YYYY
        if (preg_match_all('/(?<!\d)(\d{2})[-\/](\d{2})[-\/](\d{4})(?!\d)/', $text, $numericMatches, PREG_SET_ORDER)) {
            foreach ($numericMatches as $m) {
                $parsed = $this->safeCreateDate((int) $m[3], (int) $m[2], (int) $m[1]);
                if ($parsed !== null) {
                    $foundDates[] = $parsed;
                }
            }
        }

        // Match ISO dates: YYYY-MM-DD
        if (preg_match_all('/(?<!\d)(\d{4})-(\d{2})-(\d{2})(?!\d)/', $text, $isoMatches, PREG_SET_ORDER)) {
            foreach ($isoMatches as $m) {
                $parsed = $this->safeCreateDate((int) $m[1], (int) $m[2], (int) $m[3]);
                if ($parsed !== null) {
                    $foundDates[] = $parsed;
                }
            }
        }

        // Match Indonesian text dates: "27 November 2026"
        if (preg_match_all('/(?<!\d)(\d{1,2})\s+('.$monthPattern.')\s+(\d{4})(?!\d)/i', $text, $indoMatches, PREG_SET_ORDER)) {
            foreach ($indoMatches as $m) {
                $month = self::MONTH_MAP[strtolower($m[2])] ?? null;
                if ($month !== null) {
                    $parsed = $this->safeCreateDate((int) $m[3], $month, (int) $m[1]);
                    if ($parsed !== null) {
                        $foundDates[] = $parsed;
                    }
                }
            }
        }

        $uniqueDates = array_values(array_unique($foundDates));
        if ($uniqueDates === []) {
            return [null, null];
        }

        return [$uniqueDates[0], $uniqueDates[1] ?? $uniqueDates[0]];
    }

    /**
     * Extract start_time and end_time (HH:MM) from PDF text.
     *
     * @return array{0: ?string, 1: ?string}
     */
    private function extractTimes(string $text): array
    {
        if ($text === '') {
            return [null, null];
        }

        if (preg_match('/\b([01]?\d|2[0-3])[:.]([0-5]\d)\s*(?:-|–|—|s\.?d\.?|sampai|hingg?a)\s*([01]?\d|2[0-3])[:.]([0-5]\d)\b/i', $text, $m)) {
            return [
                sprintf('%02d:%02d', (int) $m[1], (int) $m[2]),
                sprintf('%02d:%02d', (int) $m[3], (int) $m[4]),
            ];
        }

        return [null, null];
    }

    /**
     * Extract overtime description / keterangan line from PDF text.
     */
    private function extractDescription(string $text): ?string
    {
        if ($text === '') {
            return null;
        }

        if (preg_match('/(?:Keterangan|Uraian(?:\s+Kegiatan)?|Perihal|Keperluan|Kegiatan)\s*[:\-]\s*(.+)/i', $text, $m)) {
            $desc = trim($m[1]);
            if ($desc !== '') {
                return mb_substr($desc, 0, 500);
            }
        }

        $lines = array_values(array_filter(
            array_map('trim', explode("\n", $text)),
            fn (string $line): bool => mb_strlen($line) >= 8
                && ! preg_match('/^(Tanggal|Waktu|Pukul|Nomor|SPTPL|SPKL)\b/i', $line)
        ));

        return $lines[0] ?? null;
    }

    private function safeCreateDate(int $year, int $month, int $day): ?string
    {
        if (! checkdate($month, $day, $year)) {
            return null;
        }

        return Carbon::createFromDate($year, $month, $day, 'Asia/Jakarta')->toDateString();
    }
}
