<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\OvertimeRequest;
use App\Models\User;
use App\Services\OvertimePdfImportService;
use App\Services\OvertimeService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OvertimeController extends Controller
{
    public function __construct(
        protected OvertimeService $overtimeService,
        protected OvertimePdfImportService $pdfImportService
    ) {}

    /**
     * List employee's SPTPL orders and personal WITHOUT_LETTER overtime requests.
     */
    public function index(): View
    {
        /** @var User $user */
        $user = Auth::user();
        $employee = $user->employee()->with('position')->first();

        $requests = collect();
        $monthlyStats = [
            'hours_with_letter' => 0.0,
            'hours_without_letter' => 0.0,
            'total_hours' => 0.0,
        ];

        if ($employee) {
            $requests = OvertimeRequest::with(['items' => fn ($q) => $q->where('employee_id', $employee->id)])
                ->whereHas('items', fn ($q) => $q->where('employee_id', $employee->id))
                ->orderByDesc('start_date')
                ->orderByDesc('id')
                ->paginate(10);

            $now = now('Asia/Jakarta');
            $recap = $this->overtimeService->getMonthlyOvertimeRecap($now->year, $now->month, $employee->id);
            if (isset($recap['items'][$employee->id])) {
                $monthlyStats = $recap['items'][$employee->id];
            }
        }

        return view('mobile.overtime.index', [
            'user' => $user,
            'employee' => $employee,
            'requests' => $requests,
            'monthlyStats' => $monthlyStats,
        ]);
    }

    /**
     * Show form to submit a WITHOUT_LETTER overtime request (max Day H before start time).
     */
    public function create(): View
    {
        /** @var User $user */
        $user = Auth::user();
        $employee = $user->employee()->with('position')->first();

        return view('mobile.overtime.create', [
            'user' => $user,
            'employee' => $employee,
            'todayDate' => now('Asia/Jakarta')->toDateString(),
        ]);
    }

    /**
     * AJAX endpoint to parse PDF file and extract date, time window, and description.
     */
    public function parsePdf(Request $request): JsonResponse
    {
        $request->validate([
            'pdf_file' => ['required', 'file', 'mimes:pdf', 'max:5120'],
        ], [
            'pdf_file.required' => 'Pilih berkas PDF terlebih dahulu.',
            'pdf_file.mimes' => 'Berkas harus berformat PDF.',
            'pdf_file.max' => 'Ukuran PDF maksimal 5 MB.',
        ]);

        $binary = $request->file('pdf_file')->get();
        $parsed = $this->pdfImportService->parsePdfBinary($binary);

        return response()->json([
            'success' => true,
            'data' => $parsed,
        ]);
    }

    /**
     * Persist and submit an employee WITHOUT_LETTER overtime request.
     */
    public function store(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $employee = $user->employee;

        if (! $employee) {
            return redirect()->route('mobile.overtime.index')
                ->with('error', 'Data pegawai tidak ditemukan.');
        }

        $validated = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'start_time' => ['required', 'string', 'max:5'],
            'end_time' => ['required', 'string', 'max:5'],
            'description' => ['required', 'string', 'min:5', 'max:500'],
            'attachment_pdf' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
        ], [
            'start_date.required' => 'Tanggal lembur wajib diisi.',
            'start_time.required' => 'Jam mulai lembur wajib diisi.',
            'end_time.required' => 'Jam selesai lembur wajib diisi.',
            'description.required' => 'Uraian kegiatan lembur wajib diisi.',
            'description.min' => 'Uraian kegiatan minimal 5 karakter.',
            'attachment_pdf.mimes' => 'Lampiran harus berupa berkas PDF.',
        ]);

        $attachmentPath = null;
        $importedSummary = null;
        if ($request->hasFile('attachment_pdf')) {
            $binary = $request->file('attachment_pdf')->get();
            $importedSummary = $this->pdfImportService->parsePdfBinary($binary);
            $safeCode = Str::slug($employee->employee_code ?: (string) $employee->id, '_');
            $attachmentPath = sprintf('overtime/mobile/%s_%s_%s.pdf', $safeCode, now()->format('Ymd_His'), Str::random(6));
            Storage::disk('public')->put($attachmentPath, $binary);
        }

        try {
            $created = $this->overtimeService->createRequest($user, [
                'request_type' => OvertimeRequest::TYPE_WITHOUT_LETTER,
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'] ?? $validated['start_date'],
                'attachment_pdf_path' => $attachmentPath,
                'imported_pdf_summary' => $importedSummary,
                'items' => [
                    [
                        'employee_id' => $employee->id,
                        'start_time' => $validated['start_time'],
                        'end_time' => $validated['end_time'],
                        'description' => $validated['description'],
                    ],
                ],
            ]);

            $this->overtimeService->submit($created, $user);
        } catch (ValidationException $e) {
            if ($attachmentPath && Storage::disk('public')->exists($attachmentPath)) {
                Storage::disk('public')->delete($attachmentPath);
            }

            return back()->withInput()->withErrors($e->errors());
        }

        return redirect()->route('mobile.overtime.index')
            ->with('success', 'Pengajuan lembur berhasil dikirim dan menunggu persetujuan Atasan.');
    }

    /**
     * Show overtime request details (read-only) for the employee.
     */
    public function show(OvertimeRequest $overtimeRequest): View
    {
        /** @var User $user */
        $user = Auth::user();
        $employee = $user->employee;

        if (! $employee || ! $overtimeRequest->items()->where('employee_id', $employee->id)->exists()) {
            abort(403, 'Anda tidak berwenang mengakses pengajuan lembur ini.');
        }

        $overtimeRequest->load(['items.employee', 'proposer', 'approver']);

        return view('mobile.overtime.show', [
            'user' => $user,
            'employee' => $employee,
            'overtimeRequest' => $overtimeRequest,
        ]);
    }
}
