<?php

namespace Tests\Feature\Inventory;

use App\Constants\StockMovementType;
use App\Models\Bar;
use App\Models\Counter;
use App\Models\Item;
use App\Models\StockMovement;
use App\Models\Tenant;
use App\Models\User;
use App\Services\DailySessionService;
use App\Services\Inventory\InventoryAnalyticsService;
use Tests\TestCase;

class DailySessionVarianceTest extends TestCase
{
    public function test_day_flow_has_zero_variance_when_counts_match(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::create([
            'name' => 'Test Manager',
            'email' => 'manager@example.test',
            'password' => bcrypt('password'),
            'tenant_id' => $tenant->id,
            'role' => User::ROLE_MANAGER,
        ]);

        $this->actingAs($user);

        $bar = Bar::create([
            'tenant_id' => $tenant->id,
            'name' => 'Main Bar',
            'description' => 'Test bar',
            'created_by' => $user->id,
        ]);

        $counter = Counter::create([
            'tenant_id' => $tenant->id,
            'bar_id' => $bar->id,
            'name' => 'Counter A',
            'description' => 'Main counter',
            'created_by' => $user->id,
            'assigned_user' => $user->id,
        ]);

        $item = Item::create([
            'tenant_id' => $tenant->id,
            'name' => 'Test Lager',
            'unit' => 'bottle',
            'cost_price' => 100,
            'selling_price' => 150,
            'category' => Item::CATEGORIES['BEERS'],
            'created_by' => $user->id,
        ]);

        $dailyService = new DailySessionService();
        $session = $dailyService->open($tenant->id);

        StockMovement::create([
            'tenant_id' => $tenant->id,
            'session_id' => $session->id,
            'counter_id' => $counter->id,
            'item_id' => $item->id,
            'quantity' => 10,
            'movement_type' => StockMovementType::RESTOCK,
            'movement_date' => today(),
            'created_by' => $user->id,
        ]);

        StockMovement::create([
            'tenant_id' => $tenant->id,
            'session_id' => $session->id,
            'counter_id' => $counter->id,
            'item_id' => $item->id,
            'quantity' => 4,
            'movement_type' => StockMovementType::SALE,
            'movement_date' => today(),
            'created_by' => $user->id,
        ]);

        StockMovement::create([
            'tenant_id' => $tenant->id,
            'session_id' => $session->id,
            'counter_id' => $counter->id,
            'item_id' => $item->id,
            'quantity' => 6,
            'movement_type' => StockMovementType::CLOSING,
            'movement_date' => today(),
            'created_by' => $user->id,
        ]);

        $dailyService->close($tenant->id);

        $session->refresh();
        $this->assertFalse((bool) $session->is_open);

        $analytics = new InventoryAnalyticsService($tenant->id);
        $this->assertSame(0.0, $analytics->varianceValue(today()));
    }
}
