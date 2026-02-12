<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UploadController;

Route::post('/upload', [UploadController::class, 'store']);
Route::get('/results/{id}', [UploadController::class, 'results']);

