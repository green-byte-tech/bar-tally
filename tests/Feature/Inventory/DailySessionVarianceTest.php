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
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DailySessionVarianceTest extends TestCase
{
    public function test_day_flow_has_zero_variance_when_counts_match(): void
    {
        $variance = $this->runDayFlowAndGetVariance(6);

        $this->assertSame(0.0, $variance);
    }

    public function test_day_flow_has_negative_variance_when_count_is_excess(): void
    {
        $variance = $this->runDayFlowAndGetVariance(8);

        $this->assertSame(-200.0, $variance);
    }

    public function test_day_flow_has_positive_variance_when_count_is_less(): void
    {
        $variance = $this->runDayFlowAndGetVariance(5);

        $this->assertSame(100.0, $variance);
    }

    public function test_sales_value_cost_and_gross_profit_are_calculated(): void
    {
        $analytics = $this->runDayFlowAndGetAnalytics(6);

        $this->assertSame(600.0, $analytics->salesValue());
        $this->assertSame(400.0, $analytics->costOfSales());
        $this->assertSame(200.0, $analytics->grossProfit());
    }

    public function test_opening_an_already_open_day_throws_validation_error(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::create([
            'name' => 'Test Manager',
            'email' => 'manager+' . uniqid() . '@example.test',
            'password' => bcrypt('password'),
            'tenant_id' => $tenant->id,
            'role' => User::ROLE_MANAGER,
        ]);

        $this->actingAs($user);

        $dailyService = new DailySessionService();
        $dailyService->open($tenant->id);

        $this->expectException(ValidationException::class);
        $dailyService->open($tenant->id);
    }

    public function test_closing_a_day_when_none_is_open_throws_validation_error(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::create([
            'name' => 'Test Manager',
            'email' => 'manager+' . uniqid() . '@example.test',
            'password' => bcrypt('password'),
            'tenant_id' => $tenant->id,
            'role' => User::ROLE_MANAGER,
        ]);

        $this->actingAs($user);

        $dailyService = new DailySessionService();

        $this->expectException(ValidationException::class);
        $dailyService->close($tenant->id);
    }

    public function test_opening_day_moves_yesterdays_closing_to_opening(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::create([
            'name' => 'Test Manager',
            'email' => 'manager+' . uniqid() . '@example.test',
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

        StockMovement::create([
            'tenant_id' => $tenant->id,
            'session_id' => null,
            'counter_id' => $counter->id,
            'item_id' => $item->id,
            'quantity' => 7,
            'movement_type' => StockMovementType::CLOSING,
            'movement_date' => today()->subDay(),
            'created_by' => $user->id,
        ]);

        $dailyService = new DailySessionService();
        $session = $dailyService->open($tenant->id);

        $opening = StockMovement::query()
            ->where('tenant_id', $tenant->id)
            ->where('movement_type', StockMovementType::OPENING)
            ->whereDate('movement_date', today())
            ->first();

        $this->assertNotNull($opening);
        $this->assertSame(7, $opening->quantity);
        $this->assertSame($session->id, $opening->session_id);
        $this->assertSame($counter->id, $opening->counter_id);
        $this->assertSame($item->id, $opening->item_id);
    }

    public function test_opening_day_without_yesterday_closing_creates_no_opening(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::create([
            'name' => 'Test Manager',
            'email' => 'manager+' . uniqid() . '@example.test',
            'password' => bcrypt('password'),
            'tenant_id' => $tenant->id,
            'role' => User::ROLE_MANAGER,
        ]);

        $this->actingAs($user);

        $dailyService = new DailySessionService();
        $dailyService->open($tenant->id);

        $this->assertSame(0, StockMovement::query()
            ->where('tenant_id', $tenant->id)
            ->where('movement_type', StockMovementType::OPENING)
            ->count());
    }

    public function test_opening_today_fails_when_previous_day_is_open(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::create([
            'name' => 'Test Manager',
            'email' => 'manager+' . uniqid() . '@example.test',
            'password' => bcrypt('password'),
            'tenant_id' => $tenant->id,
            'role' => User::ROLE_MANAGER,
        ]);

        $yesterdaySession = \App\Models\DailySession::create([
            'tenant_id' => $tenant->id,
            'date' => today()->subDay(),
            'opened_by' => $user->id,
            'opening_time' => now()->subDay(),
            'is_open' => true,
        ]);

        $this->actingAs($user);

        $dailyService = new DailySessionService();
        $this->expectException(ValidationException::class);
        $dailyService->open($tenant->id);
    }

    private function runDayFlowAndGetVariance(int $closingQuantity): float
    {
        $analytics = $this->runDayFlowAndGetAnalytics($closingQuantity);

        return $analytics->varianceValue(today());
    }

    private function runDayFlowAndGetAnalytics(int $closingQuantity): InventoryAnalyticsService
    {
        $tenant = Tenant::factory()->create();
        $user = User::create([
            'name' => 'Test Manager',
            'email' => 'manager+' . uniqid() . '@example.test',
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
            'quantity' => $closingQuantity,
            'movement_type' => StockMovementType::CLOSING,
            'movement_date' => today(),
            'created_by' => $user->id,
        ]);

        $dailyService->close($tenant->id);

        $session->refresh();
        $this->assertFalse((bool) $session->is_open);

        return new InventoryAnalyticsService($tenant->id);
    }
}
