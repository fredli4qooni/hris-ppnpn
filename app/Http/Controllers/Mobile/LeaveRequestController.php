<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Services\FileUploadSanitizerService;
use App\Services\LeaveRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LeaveRequestController extends Controller
{
    public function __construct(
        protected LeaveRequestService $leaveService
    ) {}

    /**
     * Tampilkan riwayat dan status pengajuan izin/cuti karyawan.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $employee = Auth::user()->employee;

        if (! $employee) {
            return redirect()->route('mobile.home')->with('error', 'Profil kepegawaian Anda belum terdaftar.');
        }

        $requests = $employee->leaveRequests()
            ->latest('id')
            ->paginate(10);

        return view('mobile.leaves.index', [
            'employee' => $employee,
            'requests' => $requests,
        ]);
    }

    /**
     * Formulir permohonan izin/cuti mandiri.
     */
    public function create(): View|RedirectResponse
    {
        $employee = Auth::user()->employee;

        if (! $employee) {
            return redirect()->route('mobile.home')->with('error', 'Profil kepegawaian Anda belum terdaftar.');
        }

        return view('mobile.leaves.create', [
            'employee' => $employee,
        ]);
    }

    /**
     * Simpan pengajuan permohonan izin/cuti baru.
     */
    public function store(Request $request): RedirectResponse
    {
        $employee = Auth::user()->employee;

        if (! $employee) {
            return redirect()->route('mobile.home')->with('error', 'Profil kepegawaian Anda belum terdaftar.');
        }

        $validated = $request->validate([
            'leave_type' => ['required', 'string', 'in:'.implode(',', [
                LeaveRequest::TYPE_ANNUAL_LEAVE,
                LeaveRequest::TYPE_SICK_LEAVE,
                LeaveRequest::TYPE_SPECIAL_LEAVE,
                LeaveRequest::TYPE_DUTY_LEAVE,
                LeaveRequest::TYPE_EARLY_DEPARTURE,
            ])],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
        ], [
            'leave_type.required' => 'Pilih jenis izin atau cuti yang diajukan.',
            'start_date.required' => 'Tanggal mulai izin wajib diisi.',
            'start_date.after_or_equal' => 'Tanggal mulai izin tidak boleh lampau.',
            'end_date.required' => 'Tanggal selesai izin wajib diisi.',
            'end_date.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
            'reason.required' => 'Alasan atau keterangan izin wajib dicantumkan.',
            'attachment.max' => 'Ukuran berkas lampiran maksimal 2 MB.',
            'attachment.mimes' => 'Format berkas lampiran harus berupa JPG, PNG, atau PDF.',
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = app(FileUploadSanitizerService::class)->sanitizeAndStore(
                $request->file('attachment'),
                'leave-attachments',
                'public',
                'attachment'
            );
        }

        try {
            $this->leaveService->submit($employee, [
                'leave_type' => $validated['leave_type'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'reason' => $validated['reason'],
                'attachment_path' => $attachmentPath,
            ]);

            return redirect()->route('mobile.leaves.index')->with('success', 'Permohonan izin/cuti berhasil diajukan dan diteruskan ke Supervisor Lapangan.');
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', 'Gagal memproses permohonan: '.$e->getMessage());
        }
    }

    /**
     * Rincian status persetujuan berjenjang permohonan izin/cuti.
     */
    public function show(LeaveRequest $leaveRequest): View|RedirectResponse
    {
        $employee = Auth::user()->employee;

        if (! $employee || $leaveRequest->employee_id !== $employee->id) {
            abort(403, 'Anda tidak berhak melihat permohonan ini.');
        }

        return view('mobile.leaves.show', [
            'employee' => $employee,
            'leaveRequest' => $leaveRequest->load(['supervisor', 'atasan']),
        ]);
    }

    /**
     * Batalkan pengajuan izin/cuti oleh pemohon.
     */
    public function cancel(LeaveRequest $leaveRequest): RedirectResponse
    {
        $employee = Auth::user()->employee;

        if (! $employee || $leaveRequest->employee_id !== $employee->id) {
            abort(403, 'Anda tidak berhak membatalkan permohonan ini.');
        }

        try {
            $this->leaveService->cancel($leaveRequest, Auth::user());

            return redirect()->route('mobile.leaves.index')->with('success', 'Permohonan izin/cuti berhasil dibatalkan.');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
