<?php

namespace App\Services;

use App\Models\DailySession;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Models\StockMovement;
use App\Constants\StockMovementType;
use Illuminate\Database\QueryException;

class DailySessionService
{
    /**
     * Get current open session (any date)
     */
    public function currentOpenAny(int $tenantId): ?DailySession
    {
        return DailySession::query()
            ->where('tenant_id', $tenantId)
            ->where('is_open', true)
            ->first();
    }

    /**
     * Get current open session for today
     */
    public function currentOpenToday(int $tenantId): ?DailySession
    {
        return DailySession::query()
            ->where('tenant_id', $tenantId)
            ->whereDate('date', today())
            ->where('is_open', true)
            ->first();
    }

    /**
     * Open day (idempotent)
     */
    public function open(int $tenantId): DailySession
    {
        try {
            return DB::transaction(function () use ($tenantId) {

                $openSession = $this->currentOpenAny($tenantId);
                if ($openSession) {
                    $message = \Carbon\Carbon::parse($openSession->date)->lt(today())
                        ? 'Close the previous day before starting a new day.'
                        : 'The day is already open.';
                    throw ValidationException::withMessages([
                        'day' => $message,
                    ]);
                }

                $dailySession = DailySession::create([
                    'tenant_id'    => $tenantId,
                    'date'         => today(),
                    'opened_by'    => Auth::id(),
                    'opening_time' => now(),
                    'is_open'      => true,
                ]);

                $this->moveOpeningStock($tenantId);

                return $dailySession;
            });
        } catch (QueryException $e) {
            throw ValidationException::withMessages([
                'day' => 'The day is already open (duplicate attempt).',
            ]);
        }
    }

    /**
     * Close day safely
     */
    public function close(int $tenantId): void
    {
        DB::transaction(function () use ($tenantId) {
            $session = $this->currentOpenAny($tenantId);

            if (! $session) {
                \Log::warning('Attempted to close day, but no open session found.', [
                    'tenant_id' => $tenantId,
                    'user_id' => Auth::id(),
                ]);
                throw ValidationException::withMessages([
                    'session' => 'No open day found to close.',
                ]);
            }

            $session->update([
                'closed_by'    => Auth::id(),
                'closing_time' => now(),
                'is_open'      => false,
            ]);

            \Log::info('Day closed successfully.', [
                'tenant_id' => $tenantId,
                'session_id' => $session->id,
                'closed_by' => Auth::id(),
                'closing_time' => now(),
            ]);
        });
    }

    /**
     * Check if day is open
     */
    public function hasOpenSession(int $tenantId): bool
    {
        return (bool) $this->currentOpenToday($tenantId);
    }

    /**
     * Check if any day is open
     */
    public function hasAnyOpenSession(int $tenantId): bool
    {
        return (bool) $this->currentOpenAny($tenantId);
    }

    /**
     * Move yesterday closing stock to today opening
     */
    public function moveOpeningStock(int $tenantId): void
    {
        $yesterday = today()->subDay();

        $items = StockMovement::query()
            ->where('tenant_id', $tenantId)
            ->where('movement_type', StockMovementType::CLOSING)
            ->whereDate('movement_date', $yesterday)
            ->get();

        if ($items->isEmpty()) {
            // throw ValidationException::withMessages([
            //     'stock' => 'No closing stock found.',
            // ]);
        }

        $session = $this->currentOpenToday($tenantId);

        if (! $session) {
            throw ValidationException::withMessages([
                'session' => 'Day must be opened before moving opening stock.',
            ]);
        }

        foreach ($items as $item) {
            StockMovement::create([
                'tenant_id'     => $tenantId,
                'item_id'       => $item->item_id,
                'counter_id'    => $item->counter_id,
                'quantity'      => $item->quantity,
                'movement_type' => StockMovementType::OPENING,
                'movement_date' => today(),
                'session_id'    => $session->id,
                'created_by'    => Auth::id(),
            ]);
        }
    }
}
