<?php

namespace Tests\Feature;

use App\Filament\Widgets\DjpbWelcomeWidget;
use App\Filament\Widgets\HrisStatsOverviewWidget;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Models\ViolationSanction;
use Database\Seeders\SampleFiveEmployeesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminDashboardWidgetsTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        $this->seed(SampleFiveEmployeesSeeder::class);

        $this->adminUser = User::where('username', 'admin')->firstOrFail();
    }

    public function test_dashboard_does_not_contain_filament_promotional_widget(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin');

        $response->assertOk();
        $response->assertDontSee('filament v5');
        $response->assertDontSee('Dokumentasi');
        $response->assertDontSee('github.com/filamentphp');
    }

    public function test_dashboard_renders_djpb_welcome_and_hris_stats_widgets(): void
    {
        $this->actingAs($this->adminUser);

        Livewire::test(DjpbWelcomeWidget::class)
            ->assertSuccessful()
            ->assertSee('Kanwil DJPb Provinsi Lampung')
            ->assertSee($this->adminUser->name)
            ->assertSee('Administrator Sistem');

        Livewire::test(HrisStatsOverviewWidget::class)
            ->assertSuccessful()
            ->assertSee('Pegawai PPNPN Aktif')
            ->assertSee('Presensi Hari Ini')
            ->assertSee('Pengajuan Cuti / Izin Pending')
            ->assertSee('Sanksi & SPK Aktif');
    }

    public function test_hris_stats_overview_widget_accurately_counts_metrics(): void
    {
        $this->actingAs($this->adminUser);

        $employee = Employee::first();

        // Create a pending leave request
        LeaveRequest::create([
            'employee_id' => $employee->id,
            'leave_type' => LeaveRequest::TYPE_ANNUAL_LEAVE,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
            'total_days' => 2,
            'reason' => 'Keperluan keluarga mendesak',
            'status' => LeaveRequest::STATUS_PENDING_SUPERVISOR,
        ]);

        // Create an active sanction
        ViolationSanction::create([
            'employee_id' => $employee->id,
            'sanction_type' => ViolationSanction::TYPE_SP_1,
            'total_points_snapshot' => 10,
            'deduction_percentage' => 5.00,
            'duration_months' => 1,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'issued_date' => now()->toDateString(),
            'is_active' => true,
        ]);

        Livewire::test(HrisStatsOverviewWidget::class)
            ->assertSuccessful()
            ->assertSee('1 Permohonan')
            ->assertSee('1 Kasus');
    }
}
