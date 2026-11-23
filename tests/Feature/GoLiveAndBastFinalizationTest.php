<?php

namespace Tests\Feature;

use App\Filament\Resources\ParallelRunReconciliations\ParallelRunReconciliationResource;
use App\Filament\Resources\UatTestCases\UatTestCaseResource;
use App\Models\BastHandover;
use App\Models\User;
use App\Services\BastHandoverService;
use Database\Seeders\SampleFiveEmployeesSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class GoLiveAndBastFinalizationTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $atasanUser;

    protected User $karyawanUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        $this->seed(SampleFiveEmployeesSeeder::class);

        $this->adminUser = User::where('username', 'admin')->firstOrFail();
        $this->atasanUser = User::where('username', 'atasan')->firstOrFail();
        $this->karyawanUser = User::where('username', 'ppnpn01')->firstOrFail();
        $this->karyawanUser->update(['must_change_password' => false]);
    }

    public function test_deployment_scripts_exist_and_contain_required_commands(): void
    {
        $ps1Path = base_path('scripts/production-deploy.ps1');
        $shPath = base_path('scripts/production-deploy.sh');

        $this->assertTrue(File::exists($ps1Path), 'PowerShell deployment script scripts/production-deploy.ps1 must exist.');
        $this->assertTrue(File::exists($shPath), 'Bash deployment script scripts/production-deploy.sh must exist.');

        $ps1Content = File::get($ps1Path);
        $shContent = File::get($shPath);

        $requiredCommands = [
            'composer install --no-dev',
            'php artisan config:cache',
            'php artisan route:cache',
            'php artisan view:cache',
            'php artisan event:cache',
            'php artisan storage:link',
            'php artisan migrate --force',
            'php artisan queue:restart',
            'php artisan up',
        ];

        foreach ($requiredCommands as $cmd) {
            $this->assertStringContainsString($cmd, $ps1Content, "production-deploy.ps1 missing command: {$cmd}");
            $this->assertStringContainsString($cmd, $shContent, "production-deploy.sh missing command: {$cmd}");
        }
    }

    public function test_bast_service_creates_or_retrieves_default_official_handover(): void
    {
        $service = app(BastHandoverService::class);
        $bast = $service->getOrCreateOfficialBast();

        $this->assertInstanceOf(BastHandover::class, $bast);
        $this->assertDatabaseHas('bast_handovers', [
            'bast_number' => 'BAST-01/WPB.08/PPK/2026',
            'contract_number' => 'SPK-20/WPB.08/PPK/2025',
            'total_sprints' => 8,
            'total_story_points' => 64,
            'status' => 'OFFICIALLY_SIGNED',
        ]);

        $this->assertTrue($bast->isFullySigned());
        $this->assertEquals('Dewi Nadila', $bast->first_party_name);
        $this->assertEquals('Hendra Setiawan, S.E., M.Si.', $bast->second_party_name);
    }

    public function test_bast_service_returns_complete_go_live_readiness_checklist(): void
    {
        $service = app(BastHandoverService::class);
        $checklist = $service->getGoLiveChecklist();

        $this->assertIsArray($checklist);
        $this->assertGreaterThanOrEqual(6, count($checklist));

        $codes = array_column($checklist, 'code');
        $this->assertContains('CHK-GL-01', $codes);
        $this->assertContains('CHK-GL-02', $codes);
        $this->assertContains('CHK-GL-03', $codes);
        $this->assertContains('CHK-GL-04', $codes);
        $this->assertContains('CHK-GL-05', $codes);
        $this->assertContains('CHK-GL-07', $codes);
    }

    public function test_bast_service_returns_8_sprint_milestones_summary(): void
    {
        $service = app(BastHandoverService::class);
        $milestones = $service->getSprintMilestonesSummary();

        $this->assertCount(8, $milestones);

        $totalSp = array_sum(array_column($milestones, 'sp'));
        $this->assertGreaterThanOrEqual(64, $totalSp);
        $this->assertEquals('SELESAI 100%', $milestones[7]['status']);
    }

    public function test_bast_and_qa_menus_are_not_registered_in_filament_navigation(): void
    {
        $panelPages = Filament::getPanel('admin')->getPages();
        $this->assertNotContains('App\Filament\Pages\BastHandoverPage', $panelPages);

        $this->assertFalse(UatTestCaseResource::shouldRegisterNavigation());
        $this->assertFalse(ParallelRunReconciliationResource::shouldRegisterNavigation());
    }

    public function test_bast_export_pdf_route_is_accessible_and_renders_correctly(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('admin.bast.export-pdf'));

        $response->assertOk();
        $response->assertSee('Berita Acara Serah Terima Pekerjaan (BAST)');
        $response->assertSee('BAST-01/WPB.08/PPK/2026');
        $response->assertSee('Dewi Nadila');
        $response->assertSee('Hendra Setiawan, S.E., M.Si.');
        $response->assertSee('Kanwil Ditjen Perbendaharaan Provinsi Lampung');
    }

    public function test_unauthorized_user_cannot_export_bast_pdf(): void
    {
        $response = $this->actingAs($this->karyawanUser)->get(route('admin.bast.export-pdf'));
        $response->assertForbidden();
    }

    public function test_mobile_home_renders_cleanly_without_obtrusive_banner(): void
    {
        $response = $this->actingAs($this->karyawanUser)->get(route('mobile.home'));

        $response->assertOk();
        $response->assertSee('Selamat datang,');
        $response->assertDontSee('SISTEM RESMI BEROPERASI (PRODUCTION LIVE)');
    }
}
