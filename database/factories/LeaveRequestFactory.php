<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\LeaveRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaveRequest>
 */
class LeaveRequestFactory extends Factory
{
    protected $model = LeaveRequest::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('now', '+1 month');
        $endDate = (clone $startDate)->modify('+1 day');

        return [
            'employee_id' => Employee::factory(),
            'leave_type' => LeaveRequest::TYPE_ANNUAL_LEAVE,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'total_days' => 2,
            'reason' => fake()->sentence(),
            'attachment_path' => null,
            'supervisor_id' => null,
            'supervisor_status' => LeaveRequest::SUPERVISOR_STATUS_PENDING,
            'supervisor_notes' => null,
            'supervisor_action_at' => null,
            'atasan_id' => null,
            'status' => LeaveRequest::STATUS_PENDING_SUPERVISOR,
            'atasan_notes' => null,
            'approved_at' => null,
            'rejected_at' => null,
        ];
    }

    /**
     * State: Diverifikasi supervisor (menunggu atasan).
     */
    public function pendingAtasan(): static
    {
        return $this->state(fn (array $attributes) => [
            'supervisor_status' => LeaveRequest::SUPERVISOR_STATUS_VERIFIED,
            'supervisor_action_at' => now(),
            'status' => LeaveRequest::STATUS_PENDING_ATASAN,
        ]);
    }

    /**
     * State: Disetujui final.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'supervisor_status' => LeaveRequest::SUPERVISOR_STATUS_VERIFIED,
            'supervisor_action_at' => now()->subDay(),
            'status' => LeaveRequest::STATUS_APPROVED,
            'approved_at' => now(),
        ]);
    }

    /**
     * State: Ditolak.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LeaveRequest::STATUS_REJECTED,
            'rejected_at' => now(),
        ]);
    }
}
