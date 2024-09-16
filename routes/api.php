<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PingController;
use App\Http\Controllers\TenderController;
use App\Http\Controllers\BidController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/ping', [PingController::class, 'ping']);
Route::post('/tenders/new', [TenderController::class, 'create']);
Route::get('/tenders', [TenderController::class, 'getTenders']);
Route::get('/tenders/my', [TenderController::class, 'getMyTenders']);
Route::get('/tenders/{tenderId}/status', [TenderController::class, 'getStatus']);
Route::put('/tenders/{tenderId}/status', [TenderController::class, 'changeStatus']);
Route::patch('/tenders/{tenderId}/edit', [TenderController::class, 'editTender']);
Route::put('/tenders/{tenderId}/rollback/{version}', [TenderController::class, 'rollbackTender']);

Route::post('/bids/new', [BidController::class, 'createBid']);
Route::get('/bids/my', [BidController::class, 'getMyBids']);
Route::post('/bids/new', [BidController::class, 'store']);

