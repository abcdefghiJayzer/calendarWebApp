<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CalendarController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/



Route::get('/', [CalendarController::class, 'index'])->name('home');
Route::get('/events', [CalendarController::class, 'getEvents'])->name('getEvents');

Route::get('/create', [CalendarController::class, 'create'])->name('create');
Route::post('/store', [CalendarController::class, 'store'])->name('store');

Route::get('/events/{id}', [CalendarController::class, 'show'])->name('events.show');



Route::get('/test', function () {
    return 'Route is working!';
});
