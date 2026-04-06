<?php

use App\Http\Controllers\MessageController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// API untuk simpan pesan ke antrean
Route::post('/send-message', [MessageController::class, 'store']);

// API untuk ambil log pesan terbaru (untuk update tabel otomatis)
Route::get('/get-messages', [MessageController::class, 'getMessages']);
Route::post('/send-bulk', [MessageController::class, 'bulkStore']);
