<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\AttendanceCorrection;
use App\Models\Employee;
use App\Models\User;
use App\Services\AttendanceCorrectionService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceCorrectionController extends Controller
{
    public function __construct(
        protected AttendanceCorrectionService $correctionService
    ) {}

    /**
     * Display a listing of attendance corrections submitted by the logged-in employee.
     */
    public function index(): View|RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        /** @var Employee|null $employee */
        $employee = $user->employee()->first();
        if (! $employee) {
            return redirect()->route('mobile.home')->with('error', 'Profil data pegawai belum terdaftar.');
        }

        $corrections = AttendanceCorrection::query()
            ->with(['supervisor', 'atasan', 'createdAttendance'])
            ->where('employee_id', $employee->id)
            ->orderByDesc('attendance_date')
            ->orderByDesc('id')
            ->paginate(15);

        return view('mobile.corrections.index', [
            'employee' => $employee,
            'corrections' => $corrections,
        ]);
    }

    /**
     * Show the form for creating a new attendance correction request.
     */
    public function create(): View|RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        /** @var Employee|null $employee */
        $employee = $user->employee()->first();
        if (! $employee) {
            return redirect()->route('mobile.home')->with('error', 'Profil data pegawai belum terdaftar.');
        }

        $todayWib = Carbon::now('Asia/Jakarta');
        // Minimum allowed date (H+2 working days back)
        $minDate = $todayWib->copy()->subDays(5)->toDateString();
        $maxDate = $todayWib->toDateString();

        return view('mobile.corrections.create', [
            'employee' => $employee,
            'minDate' => $minDate,
            'maxDate' => $maxDate,
        ]);
    }

    /**
     * Store a newly created attendance correction request.
     */
    public function store(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        /** @var Employee|null $employee */
        $employee = $user->employee()->first();
        if (! $employee) {
            return redirect()->route('mobile.home')->with('error', 'Profil data pegawai belum terdaftar.');
        }

        $validated = $request->validate([
            'attendance_date' => 'required|date',
            'correction_type' => 'required|in:CHECK_IN,CHECK_OUT,BOTH',
            'suggested_check_in_at' => 'nullable|date_format:H:i',
            'suggested_check_out_at' => 'nullable|date_format:H:i',
            'work_summary' => 'required|string|min:10|max:1000',
            'proof_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ], [
            'attendance_date.required' => 'Tanggal absensi wajib diisi.',
            'correction_type.required' => 'Pilih jenis kehadiran yang ingin dikoreksi.',
            'work_summary.required' => 'Ringkasan tugas/aktivitas dinas wajib diisi.',
            'work_summary.min' => 'Ringkasan aktivitas minimal 10 karakter.',
            'proof_file.mimes' => 'Format berkas bukti harus berupa PDF, JPG, atau PNG.',
            'proof_file.max' => 'Ukuran berkas bukti maksimal 2 MB.',
        ]);

        $proofFile = $request->file('proof_file');

        $this->correctionService->createCorrection($employee, $validated, $proofFile);

        return redirect()->route('mobile.corrections.index')
            ->with('success', 'Pengajuan koreksi lupa absen berhasil dikirim dan menunggu telaah Supervisor Lapangan.');
    }

    /**
     * Display the specified attendance correction details.
     */
    public function show(AttendanceCorrection $correction): View|RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        /** @var Employee|null $employee */
        $employee = $user->employee()->first();

        if (! $employee || $correction->employee_id !== $employee->id) {
            abort(403, 'Akses tidak diizinkan.');
        }

        $correction->load(['supervisor', 'atasan', 'createdAttendance.position']);

        return view('mobile.corrections.show', [
            'employee' => $employee,
            'correction' => $correction,
        ]);
    }
}
