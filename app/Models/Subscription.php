<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Subscription extends Model
{
    protected $fillable = [
        "user_id", "service_name", "amount", "currency", "amount_idr",
        "billing_day", "billing_cycle", "notes", "active", "next_billing_date",
    ];

    protected $casts = [
        "amount" => "decimal:2",
        "amount_idr" => "decimal:2",
        "active" => "boolean",
        "next_billing_date" => "datetime",
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function calculateNextBillingDate(): Carbon
    {
        $now = Carbon::now();
        $next = $now->copy()->day($this->billing_day);
        if ($next->isPast()) {
            $next->addMonth();
        }
        if ($this->billing_cycle === "yearly") {
            $next->year($now->year + 1);
        }
        return $next;
    }
}
