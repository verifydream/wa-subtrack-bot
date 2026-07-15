<?php

use App\Http\Controllers\WhatsAppController;
use Illuminate\Support\Facades\Route;

Route::post("/webhook", [WhatsAppController::class, "webhook"]);
Route::get("/health", fn() => response()->json(["status" => "ok", "service" => "subtrack-bot"]));
