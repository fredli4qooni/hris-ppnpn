<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LateException;
use App\Models\User;
use App\Services\LateExceptionService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;

class LateExceptionController extends Controller
{
    public function __construct(
        protected LateExceptionService $exceptionService
    ) {}

    /**
     * Display a listing of force majeure late exceptions submitted by the employee.
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

        $exceptions = LateException::query()
            ->with(['supervisor', 'atasan', 'attendance'])
            ->where('employee_id', $employee->id)
            ->orderByDesc('incident_date')
            ->orderByDesc('id')
            ->paginate(15);

        return view('mobile.late-exceptions.index', [
            'employee' => $employee,
            'exceptions' => $exceptions,
        ]);
    }

    /**
     * Show the form for creating a new force majeure dispensation request.
     */
    public function create(Request $request): View|RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        /** @var Employee|null $employee */
        $employee = $user->employee()->first();
        if (! $employee) {
            return redirect()->route('mobile.home')->with('error', 'Profil data pegawai belum terdaftar.');
        }

        $todayWib = Carbon::now('Asia/Jakarta');
        $maxDate = $todayWib->toDateString();

        // Get late attendances in current and previous month for quick selection
        $recentLateAttendances = Attendance::query()
            ->where('employee_id', $employee->id)
            ->where(function ($q) {
                $q->where('late_minutes', '>', 0)
                    ->orWhere('status', Attendance::STATUS_LATE_TL)
                    ->orWhere('deduction_percentage', '>', 0);
            })
            ->where('is_dispensation', false)
            ->whereDate('date', '<=', $maxDate)
            ->orderByDesc('date')
            ->take(15)
            ->get();

        $selectedAttendanceId = $request->query('attendance_id');

        return view('mobile.late-exceptions.create', [
            'employee' => $employee,
            'maxDate' => $maxDate,
            'recentLateAttendances' => $recentLateAttendances,
            'selectedAttendanceId' => $selectedAttendanceId,
        ]);
    }

    /**
     * Store a newly created force majeure dispensation request.
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
            'incident_date' => 'required|date',
            'attendance_id' => 'nullable|exists:attendances,id',
            'category' => 'required|in:DISASTER,ACCIDENT,FAMILY_BEREAVEMENT,OTHER_FORCE_MAJEURE',
            'incident_time' => 'nullable|date_format:H:i',
            'description' => 'required|string|min:15|max:1500',
            'proof_file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ], [
            'incident_date.required' => 'Tanggal kejadian luar biasa wajib diisi.',
            'category.required' => 'Pilih kategori kondisi kahar (Force Majeure).',
            'description.required' => 'Uraian kronologis kejadian wajib diisi.',
            'description.min' => 'Uraian kronologis minimal 15 karakter.',
            'proof_file.required' => 'Bukti dokumen resmi / foto wajib dilampirkan.',
            'proof_file.mimes' => 'Format berkas bukti harus berupa PDF, JPG, atau PNG.',
            'proof_file.max' => 'Ukuran berkas bukti maksimal 2 MB.',
        ]);

        /** @var UploadedFile $proofFile */
        $proofFile = $request->file('proof_file');

        $this->exceptionService->createException($employee, $validated, $proofFile);

        return redirect()->route('mobile.late-exceptions.index')
            ->with('success', 'Permohonan dispensasi keterlambatan kahar (Force Majeure) berhasil dikirim dan menunggu telaah Supervisor Lapangan.');
    }

    /**
     * Display the specified force majeure dispensation details.
     */
    public function show(LateException $lateException): View|RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        /** @var Employee|null $employee */
        $employee = $user->employee()->first();
        if (! $employee || $lateException->employee_id !== $employee->id) {
            abort(403, 'Anda tidak memiliki hak akses untuk melihat pengajuan dispensasi ini.');
        }

        $lateException->load(['supervisor', 'atasan', 'attendance.position']);

        return view('mobile.late-exceptions.show', [
            'employee' => $employee,
            'exception' => $lateException,
        ]);
    }
}
