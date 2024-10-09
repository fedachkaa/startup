<?php

use App\Http\Controllers\IndexController;
use Illuminate\Support\Facades\Route;

Route::get('/', [IndexController::class, 'index']);
Route::get('/risk-assessment', [IndexController::class, 'riskAssessment']);
Route::post('/risk-assessment/calculate', [IndexController::class, 'calculateRiskAssessment']);

