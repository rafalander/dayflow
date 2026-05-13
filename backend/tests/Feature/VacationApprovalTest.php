<?php

namespace Tests\Feature;

use App\Models\Cargo;
use App\Models\User;
use App\Models\VacationRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VacationApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_reject_their_own_pending_request(): void
    {
        $superadminCargo = Cargo::create([
            'name' => 'Super Admin',
            'slug' => 'dayflow-sys-superadmin-test',
            'description' => 'Cargo de teste',
            'role' => 'admin',
            'level' => 1000,
        ]);

        $superadmin = User::factory()->create([
            'cargo_id' => $superadminCargo->id,
        ]);

        $vacation = VacationRequest::create([
            'user_id' => $superadmin->id,
            'absence_type' => 'vacation',
            'approver_id' => null,
            'start_date' => now()->addDays(10)->toDateString(),
            'end_date' => now()->addDays(12)->toDateString(),
            'status' => 'pending',
            'business_days' => 3,
        ]);

        Sanctum::actingAs($superadmin);

        $response = $this->postJson("/api/vacation-requests/{$vacation->id}/reject", [
            'reason' => 'Reprovando a própria solicitação para teste.',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.status', 'rejected')
            ->assertJsonPath('data.approver_id', $superadmin->id);
    }
}
