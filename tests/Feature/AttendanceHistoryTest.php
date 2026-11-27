<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\OfficeLocation;
use App\Models\Position;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class AttendanceHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected User $karyawanUser;

    protected Employee $employee;

    protected OfficeLocation $office;

    protected Position $driverPosition;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->karyawanUser = User::where('username', 'ppnpn01')->first();
        $this->karyawanUser->update(['must_change_password' => false]);

        $this->employee = Employee::where('employee_code', 'PPNPN-2025-001')->first();
        $this->office = OfficeLocation::where('name', 'Kanwil DJPb Provinsi Lampung')->first();
        $this->driverPosition = Position::where('code', 'DRIVER')->first();
    }

    /**
     * Test unauthenticated guest cannot access attendance history page.
     */
    public function test_guest_cannot_access_attendance_history(): void
    {
        $response = $this->get('/mobile/attendance/history');

        $response->assertRedirect('/login');
    }

    /**
     * Test employee who must change password is redirected.
     */
    public function test_employee_with_must_change_password_is_redirected(): void
    {
        $this->karyawanUser->update(['must_change_password' => true]);

        $response = $this->actingAs($this->karyawanUser)->get('/mobile/attendance/history');

        $response->assertRedirect(route('password.first-change'));
    }

    /**
     * Test authenticated employee can view the attendance history page.
     */
    public function test_employee_can_view_attendance_history_page(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-18 10:00:00', 'Asia/Jakarta'));

        $response = $this->actingAs($this->karyawanUser)->get('/mobile/attendance/history');

        $response->assertOk();
        $response->assertViewIs('mobile.attendance.history');
        $response->assertViewHasAll([
            'user',
            'employee',
            'attendances',
            'summary',
            'selectedYear',
            'selectedMonth',
            'monthName',
            'currentPeriod',
            'prevPeriod',
            'nextPeriod',
        ]);
        $response->assertSee('Riwayat Presensi');
        $response->assertSee('SEN');
        $response->assertSee('MIN');
        $response->assertSee("viewMode = 'calendar'", false);
        $response->assertSee("openKpiModal('tolerance')", false);
        $response->assertSee("openKpiModal('late')", false);
        $response->assertSee('Detail Toleransi', false);
        $response->assertSee('Detail Terlambat', false);
        $response->assertDontSee('Transparansi Potongan Honor');
        $response->assertDontSee('Estimasi Potongan');
        $response->assertDontSee('Rp ');
    }

    /**
     * Test employee sees correct attendance records and summary KPI for the current month.
     */
    public function test_employee_sees_personal_attendances_and_kpi_summary(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-20 12:00:00', 'Asia/Jakarta'));

        // 1. Record on-time attendance on 2026-11-10
        Attendance::create([
            'employee_id' => $this->employee->id,
            'position_id' => $this->driverPosition->id,
            'office_location_id' => $this->office->id,
            'date' => '2026-11-10',
            'check_in_at' => '2026-11-10 06:55:00',
            'check_out_at' => '2026-11-10 18:05:00',
            'check_in_lat' => (float) $this->office->latitude,
            'check_in_lng' => (float) $this->office->longitude,
            'check_out_lat' => (float) $this->office->latitude,
            'check_out_lng' => (float) $this->office->longitude,
            'check_in_distance_meters' => 15.0,
            'check_out_distance_meters' => 18.0,
            'status' => Attendance::STATUS_ON_TIME,
            'check_out_status' => Attendance::CHECK_OUT_ON_TIME,
            'late_minutes' => 0,
            'required_compensation_minutes' => 0,
            'early_leave_minutes' => 0,
            'is_compensated' => true,
            'tl_category' => Attendance::TL_NONE,
            'deduction_percentage' => 0.00,
            'shift_type' => Attendance::SHIFT_NORMAL,
        ]);

        // 2. Record tolerance attendance on 2026-11-12 (late 10m, compensated)
        Attendance::create([
            'employee_id' => $this->employee->id,
            'position_id' => $this->driverPosition->id,
            'office_location_id' => $this->office->id,
            'date' => '2026-11-12',
            'check_in_at' => '2026-11-12 07:10:00',
            'check_out_at' => '2026-11-12 18:12:00',
            'check_in_lat' => (float) $this->office->latitude,
            'check_in_lng' => (float) $this->office->longitude,
            'check_out_lat' => (float) $this->office->latitude,
            'check_out_lng' => (float) $this->office->longitude,
            'check_in_distance_meters' => 20.0,
            'check_out_distance_meters' => 22.0,
            'status' => Attendance::STATUS_TOLERANCE,
            'check_out_status' => Attendance::CHECK_OUT_COMPENSATED,
            'late_minutes' => 10,
            'required_compensation_minutes' => 10,
            'early_leave_minutes' => 0,
            'is_compensated' => true,
            'tl_category' => Attendance::TL_NONE,
            'deduction_percentage' => 0.00,
            'shift_type' => Attendance::SHIFT_NORMAL,
        ]);

        // 3. Record late TL 2 attendance on 2026-11-15 (late 35m, deduction 1.00%)
        Attendance::create([
            'employee_id' => $this->employee->id,
            'position_id' => $this->driverPosition->id,
            'office_location_id' => $this->office->id,
            'date' => '2026-11-15',
            'check_in_at' => '2026-11-15 07:35:00',
            'check_out_at' => '2026-11-15 18:00:00',
            'check_in_lat' => (float) $this->office->latitude,
            'check_in_lng' => (float) $this->office->longitude,
            'check_out_lat' => (float) $this->office->latitude,
            'check_out_lng' => (float) $this->office->longitude,
            'check_in_distance_meters' => 12.0,
            'check_out_distance_meters' => 14.0,
            'status' => Attendance::STATUS_LATE_TL,
            'check_out_status' => Attendance::CHECK_OUT_ON_TIME,
            'late_minutes' => 35,
            'required_compensation_minutes' => 0,
            'early_leave_minutes' => 0,
            'is_compensated' => true,
            'tl_category' => Attendance::TL_2,
            'deduction_percentage' => 1.00,
            'shift_type' => Attendance::SHIFT_NORMAL,
        ]);

        // 4. Record early leave (PSW) attendance on 2026-11-18 (early leave 30m)
        Attendance::create([
            'employee_id' => $this->employee->id,
            'position_id' => $this->driverPosition->id,
            'office_location_id' => $this->office->id,
            'date' => '2026-11-18',
            'check_in_at' => '2026-11-18 07:00:00',
            'check_out_at' => '2026-11-18 17:30:00',
            'check_in_lat' => (float) $this->office->latitude,
            'check_in_lng' => (float) $this->office->longitude,
            'check_out_lat' => (float) $this->office->latitude,
            'check_out_lng' => (float) $this->office->longitude,
            'check_in_distance_meters' => 10.0,
            'check_out_distance_meters' => 15.0,
            'status' => Attendance::STATUS_ON_TIME,
            'check_out_status' => Attendance::CHECK_OUT_PSW,
            'late_minutes' => 0,
            'required_compensation_minutes' => 0,
            'early_leave_minutes' => 30,
            'is_compensated' => false,
            'tl_category' => Attendance::TL_NONE,
            'deduction_percentage' => 0.00,
            'shift_type' => Attendance::SHIFT_NORMAL,
        ]);

        $response = $this->actingAs($this->karyawanUser)->get('/mobile/attendance/history');

        $response->assertOk();

        /** @var Collection<int, Attendance> $viewAttendances */
        $viewAttendances = $response->viewData('attendances');
        $this->assertCount(4, $viewAttendances);

        /** @var array<string, mixed> $summary */
        $summary = $response->viewData('summary');
        $this->assertEquals(4, $summary['total_days']);
        $this->assertEquals(2, $summary['on_time_count']);
        $this->assertEquals(1, $summary['tolerance_count']);
        $this->assertEquals(1, $summary['tolerance_compensated_count']);
        $this->assertEquals(0, $summary['tolerance_uncompensated_count']);
        $this->assertEquals(1, $summary['late_tl_count']);
        $this->assertEquals(1, $summary['tl_2_count']);
        $this->assertEquals(1, $summary['psw_count']);
        $this->assertEquals(1.00, $summary['total_deduction_percentage']);

        // Check deduction amount based on base salary
        $expectedDeduction = round(((float) $this->employee->base_salary * 1.00) / 100, 2);
        $this->assertEquals($expectedDeduction, $summary['estimated_deduction_amount']);
    }

    /**
     * Test filtering attendance history by month and year.
     */
    public function test_filter_attendance_history_by_month_and_year(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-20 12:00:00', 'Asia/Jakarta'));

        // October record
        Attendance::create([
            'employee_id' => $this->employee->id,
            'position_id' => $this->driverPosition->id,
            'office_location_id' => $this->office->id,
            'date' => '2026-10-25',
            'check_in_at' => '2026-10-25 06:58:00',
            'check_out_at' => '2026-10-25 18:02:00',
            'status' => Attendance::STATUS_ON_TIME,
            'check_out_status' => Attendance::CHECK_OUT_ON_TIME,
            'late_minutes' => 0,
            'required_compensation_minutes' => 0,
            'early_leave_minutes' => 0,
            'is_compensated' => true,
            'tl_category' => Attendance::TL_NONE,
            'deduction_percentage' => 0.00,
            'shift_type' => Attendance::SHIFT_NORMAL,
        ]);

        // November record
        Attendance::create([
            'employee_id' => $this->employee->id,
            'position_id' => $this->driverPosition->id,
            'office_location_id' => $this->office->id,
            'date' => '2026-11-15',
            'check_in_at' => '2026-11-15 06:55:00',
            'check_out_at' => '2026-11-15 18:00:00',
            'status' => Attendance::STATUS_ON_TIME,
            'check_out_status' => Attendance::CHECK_OUT_ON_TIME,
            'late_minutes' => 0,
            'required_compensation_minutes' => 0,
            'early_leave_minutes' => 0,
            'is_compensated' => true,
            'tl_category' => Attendance::TL_NONE,
            'deduction_percentage' => 0.00,
            'shift_type' => Attendance::SHIFT_NORMAL,
        ]);

        // Filter for October 2026
        $response = $this->actingAs($this->karyawanUser)->get('/mobile/attendance/history?month=10&year=2026');

        $response->assertOk();
        $this->assertEquals(10, $response->viewData('selectedMonth'));
        $this->assertEquals(2026, $response->viewData('selectedYear'));

        /** @var Collection<int, Attendance> $octoberAttendances */
        $octoberAttendances = $response->viewData('attendances');
        $this->assertCount(1, $octoberAttendances);
        $this->assertEquals('2026-10-25', $octoberAttendances->first()->date->toDateString());
    }

    /**
     * Test employee only sees their own attendance records (isolation).
     */
    public function test_employee_only_sees_their_own_records(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-20 12:00:00', 'Asia/Jakarta'));

        $otherUser = User::create([
            'username' => 'ppnpn02',
            'name' => 'Budi Santoso',
            'email' => 'ppnpn02@djpb.kemenkeu.go.id',
            'password' => bcrypt('password'),
            'is_active' => true,
            'must_change_password' => false,
        ]);
        $otherUser->syncRoles(['karyawan']);

        $otherEmployee = Employee::create([
            'user_id' => $otherUser->id,
            'employee_code' => 'PPNPN-2025-002',
            'full_name' => 'Budi Santoso',
            'gender' => 'L',
            'phone_number' => '081234567891',
            'position_id' => $this->driverPosition->id,
            'base_salary' => 3000000,
            'leave_quota_balance' => 12,
            'is_active' => true,
        ]);

        // Record for this employee
        Attendance::create([
            'employee_id' => $this->employee->id,
            'position_id' => $this->driverPosition->id,
            'office_location_id' => $this->office->id,
            'date' => '2026-11-18',
            'check_in_at' => '2026-11-18 06:50:00',
            'check_out_at' => '2026-11-18 18:00:00',
            'status' => Attendance::STATUS_ON_TIME,
            'check_out_status' => Attendance::CHECK_OUT_ON_TIME,
            'late_minutes' => 0,
            'required_compensation_minutes' => 0,
            'early_leave_minutes' => 0,
            'is_compensated' => true,
            'tl_category' => Attendance::TL_NONE,
            'deduction_percentage' => 0.00,
            'shift_type' => Attendance::SHIFT_NORMAL,
        ]);

        // Record for other employee
        Attendance::create([
            'employee_id' => $otherEmployee->id,
            'position_id' => $this->driverPosition->id,
            'office_location_id' => $this->office->id,
            'date' => '2026-11-18',
            'check_in_at' => '2026-11-18 06:55:00',
            'check_out_at' => '2026-11-18 18:05:00',
            'status' => Attendance::STATUS_ON_TIME,
            'check_out_status' => Attendance::CHECK_OUT_ON_TIME,
            'late_minutes' => 0,
            'required_compensation_minutes' => 0,
            'early_leave_minutes' => 0,
            'is_compensated' => true,
            'tl_category' => Attendance::TL_NONE,
            'deduction_percentage' => 0.00,
            'shift_type' => Attendance::SHIFT_NORMAL,
        ]);

        $response = $this->actingAs($this->karyawanUser)->get('/mobile/attendance/history');

        $response->assertOk();

        /** @var Collection<int, Attendance> $attendances */
        $attendances = $response->viewData('attendances');
        $this->assertCount(1, $attendances);
        $this->assertEquals($this->employee->id, $attendances->first()->employee_id);
    }

    /**
     * Test check-in and check-out times are accurately displayed in WIB without +7h timezone shift (Milestone 1 - Poin 4).
     */
    public function test_history_displays_accurate_wib_times_without_timezone_shift(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-27 07:20:00', 'Asia/Jakarta'));

        $img = imagecreatetruecolor(50, 50);
        ob_start();
        imagejpeg($img, null, 70);
        $jpegBinary = ob_get_clean();
        imagedestroy($img);
        $photoBase64 = 'data:image/jpeg;base64,'.base64_encode($jpegBinary);

        // Perform check-in at 07:20 WIB
        $this->actingAs($this->karyawanUser)
            ->postJson('/mobile/attendance/check-in', [
                'position_id' => $this->driverPosition->id,
                'latitude' => (float) $this->office->latitude,
                'longitude' => (float) $this->office->longitude,
                'liveness_verified' => true,
                'photo' => $photoBase64,
            ])
            ->assertOk();

        // Perform check-out at 18:05 WIB (after Driver 18:00 schedule end)
        Carbon::setTestNow(Carbon::parse('2026-11-27 18:05:00', 'Asia/Jakarta'));
        $this->actingAs($this->karyawanUser)
            ->postJson('/mobile/attendance/check-out', [
                'latitude' => (float) $this->office->latitude,
                'longitude' => (float) $this->office->longitude,
                'liveness_verified' => true,
                'photo' => $photoBase64,
            ])
            ->assertOk();

        $response = $this->actingAs($this->karyawanUser)->get('/mobile/attendance/history?month=11&year=2026');

        $response->assertOk();
        $response->assertSee('07:20 WIB');
        $response->assertSee('18:05 WIB');
        $response->assertSee('07:20');
        $response->assertSee('18:05');
        $response->assertDontSee('14:20 WIB');

        // Verify calendarDays structure passed to view
        $calendarDays = $response->viewData('calendarDays');
        $this->assertCount(30, $calendarDays);
        $day27 = collect($calendarDays)->firstWhere('day', 27);
        $this->assertEquals('07:20', $day27['check_in_short']);
        $this->assertEquals('18:05', $day27['check_out_short']);

        // Verify photo URLs are root-relative same-origin (/storage/...) so CSP img-src 'self' allows them on any host
        /** @var Collection<int, Attendance> $attendances */
        $attendances = $response->viewData('attendances');
        $att = $attendances->first();
        $this->assertStringStartsWith('/storage/', $att->check_in_photo_url);
        $this->assertStringStartsWith('/storage/', $att->check_out_photo_url);
    }
}
