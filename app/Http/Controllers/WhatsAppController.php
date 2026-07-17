<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Subscription;
use App\Services\MessageParser;
use App\Services\CurrencyService;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WhatsAppController extends Controller
{
    /**
     * WhatsAppController constructor.
     *
     * @param MessageParser $parser Service to parse natural language messages.
     * @param CurrencyService $currency Service to convert currencies to IDR.
     * @param WhatsAppService $whatsapp Service to interact with the Fonnte WhatsApp API.
     */
    public function __construct(
        private MessageParser $parser,
        private CurrencyService $currency,
        private WhatsAppService $whatsapp,
    ) {}

    /**
     * Handles incoming webhooks from Fonnte.
     *
     * @param Request $request The incoming HTTP request.
     * @return \Illuminate\Http\JsonResponse JSON response indicating status.
     */
    public function webhook(Request $request)
    {
        $phone = $request->input('phone') ?? $request->input('number', '');
        $message = $request->input('message') ?? $request->input('text', '');

        if (empty($phone) || empty($message)) {
            return response()->json(['status' => 'ignored']);
        }

        $phone = preg_replace('/^\+?0*/', '', trim($phone));
        $message = trim($message);

        $user = $this->getOrCreateUser($phone);
        $this->handleCommand($user, $message);

        return response()->json(['status' => 'ok']);
    }

    /**
     * Retrieves an existing user or creates a new one based on phone number.
     *
     * @param string $phone The sanitized phone number.
     * @return User The found or newly created user model.
     */
    private function getOrCreateUser(string $phone): User
    {
        return User::firstOrCreate(
            ['phone' => $phone],
            ['name' => "User {$phone}", 'email' => "{$phone}@subtrack.local"]
        );
    }

    /**
     * Routes the incoming message to the appropriate command handler or parser.
     *
     * @param User $user The user sending the message.
     * @param string $message The message content.
     * @return void
     */
    private function handleCommand(User $user, string $message): void
    {
        $lower = Str::lower($message);

        if (in_array($lower, ['/list', 'list', 'daftar'])) {
            $this->showList($user);
            return;
        }

        if (in_array($lower, ['/total', 'total'])) {
            $this->showTotal($user);
            return;
        }

        if (in_array($lower, ['/help', 'bantuan', 'help'])) {
            $this->showHelp($user);
            return;
        }

        if (preg_match('/^(hapus|del|delete)\s+(.+)/i', $message, $m)) {
            $this->deleteSubscription($user, $m[2]);
            return;
        }

        $parsed = $this->parser->parse($message);
        if ($parsed) {
            $this->addSubscription($user, $parsed);
            return;
        }

        $this->whatsapp->sendText($user->phone, 'Maaf, saya tidak mengerti. Ketik *help* untuk bantuan.');
    }

    /**
     * Adds a new subscription to the database and sends a confirmation message.
     *
     * @param User $user The user who owns the subscription.
     * @param array $data Parsed subscription data containing service_name, amount, currency, and billing_day.
     * @return void
     */
    private function addSubscription(User $user, array $data): void
    {
        $amountIdr = $this->currency->toIDR($data['amount'], $data['currency']);

        $sub = Subscription::create([
            'user_id' => $user->id,
            'service_name' => $data['service_name'],
            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'amount_idr' => $amountIdr,
            'billing_day' => $data['billing_day'],
            'billing_cycle' => 'monthly',
            'notes' => $data['notes'],
            'active' => true,
            'next_billing_date' => now()->day($data['billing_day'])->isPast()
                ? now()->addMonth()->day($data['billing_day'])
                : now()->day($data['billing_day']),
        ]);

        $formatted = $this->formatCurrency($data['amount'], $data['currency']);
        $this->whatsapp->sendText($user->phone,
            "✅ Tercatat!\n\n" .
            "*{$sub->service_name}*\n" .
            "💰 {$formatted}/bulan\n" .
            "📅 Tagihan tanggal {$sub->billing_day}\n" .
            "⏰ Reminder H-3 & H-1 sebelum jatuh tempo"
        );
    }

    /**
     * Shows a list of all active subscriptions for the given user.
     *
     * @param User $user The user requesting the list.
     * @return void
     */
    private function showList(User $user): void
    {
        $subs = $user->subscriptions()->where('active', true)->get();

        if ($subs->isEmpty()) {
            $this->whatsapp->sendText($user->phone,
                "📋 Belum ada langganan tercatat.\n\nKirim pesan seperti:\n*Netflix 149000 rb tanggal 15*"
            );
            return;
        }

        $total = $subs->sum('amount_idr');
        $lines = ["📋 *Daftar Langganan Aktif*\n"];

        foreach ($subs as $i => $sub) {
            $formatted = $this->formatCurrency($sub->amount, $sub->currency);
            $num = $i + 1;
            $lines[] = "{$num}. *{$sub->service_name}* — {$formatted} (tgl {$sub->billing_day})";
        }

        $totalFormatted = number_format($total, 0, ',', '.');
        $lines[] = "\n💰 *Total/bulan:* Rp {$totalFormatted}";
        $this->whatsapp->sendText($user->phone, implode("\n", $lines));
    }

    /**
     * Calculates and sends the total monthly cost of all active subscriptions in IDR.
     *
     * @param User $user The user requesting the total.
     * @return void
     */
    private function showTotal(User $user): void
    {
        $total = $user->subscriptions()->where('active', true)->sum('amount_idr');
        $totalFormatted = number_format($total, 0, ',', '.');
        $this->whatsapp->sendText($user->phone,
            "💰 *Total Langganan/Bulan*\n\n" .
            "Rp {$totalFormatted}\n\n" .
            "Ketik *list* untuk melihat detail."
        );
    }

    /**
     * Deactivates a subscription matching the given name and notifies the user.
     *
     * @param User $user The user deleting the subscription.
     * @param string $name The name or partial name of the subscription to delete.
     * @return void
     */
    private function deleteSubscription(User $user, string $name): void
    {
        $sub = $user->subscriptions()
            ->where('active', true)
            ->where('service_name', 'like', "%{$name}%")
            ->first();

        if (!$sub) {
            $this->whatsapp->sendText($user->phone, "❌ Langganan \"{$name}\" tidak ditemukan.");
            return;
        }

        $sub->update(['active' => false]);
        $this->whatsapp->sendText($user->phone, "🗑️ *{$sub->service_name}* telah dihapus dari daftar langganan.");
    }

    /**
     * Sends a help message detailing available commands and syntax.
     *
     * @param User $user The user requesting help.
     * @return void
     */
    private function showHelp(User $user): void
    {
        $this->whatsapp->sendText($user->phone,
            "🤖 *SubTrack Bot — Bantuan*\n\n" .
            "Cara mencatat langganan:\n" .
            "*[Nama] [harga] tanggal [tgl]*\n\n" .
            "Contoh:\n" .
            "• Netflix 149000 rb tanggal 15\n" .
            "• Hosting digitalocean 15 dollar tiap tanggal 1\n" .
            "• Spotify premium 55000 tanggal 20\n\n" .
            "Perintah:\n" .
            "• *list* — Lihat semua langganan\n" .
            "• *total* — Lihat total biaya/bulan\n" .
            "• *hapus [nama]* — Hapus langganan\n" .
            "• *help* — Tampilkan bantuan ini\n\n" .
            "💡 Bot akan mengingatkan Anda H-3 dan H-1 sebelum jatuh tempo."
        );
    }

    /**
     * Formats an amount into its respective currency display format.
     *
     * @param float $amount The monetary amount.
     * @param string $currency The currency code (IDR, USD, EUR).
     * @return string The formatted currency string.
     */
    private function formatCurrency(float $amount, string $currency): string
    {
        return match ($currency) {
            'USD' => '$' . number_format($amount, 2),
            'EUR' => '€' . number_format($amount, 2),
            default => 'Rp ' . number_format($amount, 0, ',', '.'),
        };
    }
}
