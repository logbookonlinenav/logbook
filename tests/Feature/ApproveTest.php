<?php

namespace Tests\Feature;

use App\Models\Logbook;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApproveTest extends TestCase
{
    use RefreshDatabase;

	private function createRegularUser()
    {
        return User::factory()->create(['access_level' => '0']); 
    }

    private function createApproverUser()
    {
        return User::factory()->create(['access_level' => '1']); 
    }
    public function test_supervisor_can_approve_logbook(): void
    {
        $supervisor = $this->createApproverUser();
        $unit = Unit::create(['nama' => 'Unit Approve']);
        
        $logbook = Logbook::create([
            'unit_id' => $unit->id,
            'judul' => 'Pending Logbook',
            'date' => now(),
            'shift' => '1',
            'created_by' => User::factory()->create()->id,
            'is_approved' => 0
        ]);

        $response = $this
            ->actingAs($supervisor)
            ->put(route('logbook.approve', ['unit_id' => $unit->id, 'logbook_id' => $logbook->id]));

        $response->assertRedirect();
        $response->assertSessionHas('successMessage', 'Status logbook: Disetujui');

        $this->assertDatabaseHas('logbooks', [
            'id' => $logbook->id,
            'is_approved' => 1,
            'approved_by' => $supervisor->id,
        ]);
    }

    public function test_regular_user_cannot_approve_logbook(): void
    {
        $user = $this->createRegularUser();
        $unit = Unit::create(['nama' => 'Unit Deny']);
        
        $logbook = Logbook::create([
            'unit_id' => $unit->id,
            'judul' => 'Logbook',
            'date' => now(),
            'shift' => '1',
            'created_by' => User::factory()->create()->id,
            'is_approved' => 0
        ]);

        $response = $this
            ->actingAs($user)
            ->put(route('logbook.approve', ['unit_id' => $unit->id, 'logbook_id' => $logbook->id]));

		$response->assertRedirect();
        $response->assertSessionHas('errorMessage', 'Anda tidak memiliki hak akses');
        
        $this->assertDatabaseHas('logbooks', [
            'id' => $logbook->id,
            'is_approved' => 0,
        ]);
    }
}