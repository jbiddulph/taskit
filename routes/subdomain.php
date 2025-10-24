<?php

use App\Http\Controllers\SubdomainController;
use Illuminate\Support\Facades\Route;

// Subdomain routes - only loaded when SubdomainMiddleware detects a subdomain
Route::get('/', [SubdomainController::class, 'company'])->name('subdomain.company');
Route::get('/login', [SubdomainController::class, 'login'])->name('subdomain.login');
Route::post('/login', [SubdomainController::class, 'authenticate'])->name('subdomain.authenticate');
Route::get('/dashboard', [SubdomainController::class, 'dashboard'])->name('subdomain.dashboard');
