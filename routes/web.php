<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'service' => 'wa-subtrack-bot',
        'status' => 'running',
        'docs' => '/api/health',
        'github' => 'https://github.com/verifydream/wa-subtrack-bot',
    ]);
});
