<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Services\WhatsAppService;
use App\Services\CurrencyService;
use Illuminate\Console\Command;
use Carbon\Carbon;

class SendReminders extends Command
{
    protected $signature = "subtrack:reminders";
    protected $description = "Send H-3 and H-1 billing reminders";

    public function handle(WhatsAppService $whatsapp, CurrencyService $currency): int
    {
        $today = Carbon::now();
        $subs = Subscription::where("active", true)
            ->whereNotNull("next_billing_date")
            ->get();

        $sent = 0;

        foreach ($subs as $sub) {
            $nextBilling = Carbon::parse($sub->next_billing_date);
            $daysUntil = $today->diffInDays($nextBilling, false);

            $message = null;

            if ($daysUntil == -3) {
                $message = $this->buildReminder($sub, 3);
            } elseif ($daysUntil == -1) {
                $message = $this->buildReminder($sub, 1);
            } elseif ($daysUntil < 0) {
                // Past due — reset to next month
                $sub->update([
                    "next_billing_date" => $sub->calculateNextBillingDate(),
                ]);
                continue;
            }

            if ($message && $sub->user && $sub->user->phone) {
                $whatsapp->sendText($sub->user->phone, $message);
                $sent++;
            }
        }

        $this->info("Sent {$sent} reminders.");
        return self::SUCCESS;
    }

    private function buildReminder(Subscription $sub, int $days): string
    {
        $formatted = match ($sub->currency) {
            "USD" => "$" . number_format($sub->amount, 2),
            "EUR" => "€" . number_format($sub->amount, 2),
            default => "Rp " . number_format($sub->amount_idr ?? $sub->amount, 0, ",", "."),
        };

        $urgency = $days === 1 ? "⚠️ *H-1!* Besok jatuh tempo!" : "🔔 Reminder H-3";

        return "{$urgency}\n\n" .
            "*{$sub->service_name}*\n" .
            "💰 {$formatted}\n" .
            "📅 Jatuh tempo: " . Carbon::parse($sub->next_billing_date)->format("d M Y") .
            "\n\nPastikan saldo cukup ya! 🙏";
    }
}
