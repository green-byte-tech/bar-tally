<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Counter extends Model
{
    //
    protected $fillable = [
        'tenant_id',
        'bar_id',
        'name',
        'description',
        'created_by',
        'updated_by',
        'assigned_user',
    ];

    /* Relationships */

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

        public function bar()
    {
        return $this->belongsTo(Bar::class);
    }

    public function movements()
    {
        return $this->hasMany(StockMovement::class);
    }
    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_user');
    }
}
