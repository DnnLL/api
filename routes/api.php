<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CashDispenserController;

    Route::post('/deposit', [CashDispenserController::class, 'deposit']);
    Route::post('/withdraw', [CashDispenserController::class, 'withdraw']);
    Route::get('/bills', [CashDispenserController::class, 'getBills']);
    

