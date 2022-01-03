<?php

declare(strict_types=1);

use App\Http\Controllers\BankAccountController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\EventUserTokenController;
use App\Http\Controllers\EventUserController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\PINCodeController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use App\Models\Event;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| Here you can register the tenant routes for your application.
| These routes are loaded by the TenantRouteServiceProvider.
|
| Feel free to customize them however you want. Good luck!
|
*/

// Universal API routes - no auth - throttling.
Route::prefix(
    'api'
)->middleware([
    'universal',
    InitializeTenancyByDomain::class,
    'api',
    'throttle:open',
])->group(function () {
    Route::post('register', RegisterController::class);
    Route::post('login', LoginController::class);
    Route::put('pincode/{user}', PINCodeController::class); // Route used to update pin code
});

// Universal API routes - auth.
Route::prefix(
    'api'
)->middleware([
    'universal',
    InitializeTenancyByDomain::class,
    'api',
    'auth:sanctum',
])->group(function () {
    //Route::get('/token/refresh', [TokenController::class, 'refresh']);
    Route::get('users', [UserController::class, 'index'])->middleware('ability:admin,manager');
    Route::get('users/{user}', [UserController::class, 'show'])->middleware(['ability:admin,manager,seller', 'own']);
    Route::patch('users/{user}', [UserController::class, 'update'])->middleware(['ability:admin,manager,seller', 'own']);
    Route::delete('users/{user}', [UserController::class, 'destroy'])->middleware('ability:admin');
});

// Tenant API routes - auth
Route::prefix(
    'api'
)->middleware([
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
    'api',
    'auth:sanctum'
])->group(function () {
    // This route is used to sync the user's role tokens between the server and the client.
    // Route::post('token/sync', [EventUserTokenController::class, 'sync']);

    // This route is used by admin to seed a new privileged/unprivileged user in the database.
    Route::post('users', [UserController::class, 'seed'])->middleware('ability:admin,manager');
    // This route is to activate or deactivate a user. The user's token isj revoked upon deactivation.
    Route::post('users/{user}', [UserController::class, 'toggleIsActive'])->middleware('ability:admin,manager');
    // This route is used to access the user events.
    Route::get('users/{user}/events', [UserController::class, 'events'])->middleware(['ability:admin,manager,seller', 'own']);

    Route::get('events', [EventController::class, 'index'])->can('viewAny', Event::class);
    Route::post('events', [EventController::class, 'store'])->middleware('ability:admin,manager');
    Route::get('events/{event}', [EventController::class, 'show'])->can('view', 'event');
    Route::patch('events/{event}', [EventController::class, 'update'])->middleware(['ability:admin,manager']);
    Route::delete('events/{event}', [EventController::class, 'destroy'])->middleware('ability:admin');

    Route::get('bankaccounts', [BankAccountController::class, 'index'])->middleware('ability:admin,manager');
    Route::post('bankaccounts', [BankAccountController::class, 'store'])->middleware('ability:admin,manager');
    Route::get('bankaccounts/{bankAccount}', [BankAccountController::class, 'show'])->middleware(['ability:admin,manager']);
    Route::patch('bankaccounts/{bankAccount}', [BankAccountController::class, 'update'])->middleware(['ability:admin,manager']);
    Route::delete('bankaccounts/{bankAccount}', [BankAccountController::class, 'destroy'])->middleware('ability:admin');

    // This route is used to access the event users.
    Route::get('events/{event}/users', [EventController::class, 'users'])->middleware(['ability:admin,manager', 'member']);

    // There routes are used to upsert and delete roles on the pivot table.
    Route::put('events/{event}/users/{user}', [EventUserController::class, 'upsert'])->middleware(['ability:admin,manager', 'member']);
    Route::delete('events/{event}/users/{user}', [EventUserController::class, 'destroy'])->middleware(['ability:admin,manager', 'member']); // Detach is within scope of manager.

    // This route is used to access the user transactions executed during an event.
    Route::get('events/{event}/users/{user}/transactions', [EventUserController::class, 'transactions'])->middleware(['ability:admin,manager,seller', 'member', 'own']);

    // This route is used to access the event categories.
    Route::get('events/{event}/categories', [EventController::class, 'categories'])->middleware(['ability:admin,manager', 'member']);

    Route::get('categories', [CategoryController::class, 'index'])->middleware(['ability:admin,manager', 'member']);
    Route::post('events/{event}/categories', [CategoryController::class, 'store'])->middleware(['ability:admin,manager', 'member']);
    Route::get('categories/{category}', [CategoryController::class, 'show'])->middleware(['ability:admin,manager', 'member']);
    Route::patch('categories/{category}', [CategoryController::class, 'update'])->middleware(['ability:admin,manager', 'member']);
    Route::delete('categories/{category}', [CategoryController::class, 'destroy'])->middleware('ability:admin');

    // This route is used to access the category items.
    Route::get('categories/{category}/items', [CategoryController::class, 'items'])->middleware(['ability:admin,manager', 'member']);

    Route::get('items', [ItemController::class, 'index'])->middleware(['ability:admin,manager', 'member']);
    Route::post('categories/{category}/items', [ItemController::class, 'store'])->middleware(['ability:admin,manager', 'member']);
    Route::get('items/{item}', [ItemController::class, 'show'])->middleware(['ability:admin,manager', 'member']);
    Route::patch('items/{item}', [ItemController::class, 'update'])->middleware(['ability:admin,manager', 'member']);
    Route::delete('items/{item}', [ItemController::class, 'destroy'])->middleware('ability:admin');

    // This route is used to access the event transactions.
    Route::get('events/{event}/transactions', [EventController::class, 'transactions'])->middleware(['ability:admin,manager', 'member']);
    // This route is used to access the item transactions.
    Route::get('items/{item}/transactions', [ItemController::class, 'transactions'])->middleware(['ability:admin,manager', 'member']);

    Route::get('transactions', [TransactionController::class, 'index'])->middleware(['ability:admin,manager', 'member']);
    Route::post('events/{event}/transactions', [TransactionController::class, 'store'])->middleware(['ability:admin,manager,seller', 'member']); // This route also inserts the pivot table entries.
    Route::get('transactions/{transaction}', [TransactionController::class, 'show'])->middleware(['ability:admin,manager', 'member']);
    Route::delete('transactions/{transaction}', [TransactionController::class, 'destroy'])->middleware('ability:admin');

    // This route is used to access the transaction items.
    Route::get('transactions/{transaction}/items', [TransactionController::class, 'items'])->middleware(['ability:admin,manager,seller', 'member', 'own']);

    // This route is used to modify the status of a transaction.
    Route::post('transactions/{transaction}', [TransactionController::class, 'toggleStatus'])->middleware(['ability:admin,manager', 'member']);

    // This route is used to access the user transactions.
    // Route::get('users/{user}/transactions', [UserController::class, 'transactions'])->middleware('ability:admin,manager,seller');

    // This route should be visited prior to a sync with all the role tokens possessed by the client. 
    // Route::post('token/purge', [EventUserTokenController::class, 'purge']);
});


Route::fallback(function () {
    return response()->json(['message' => 'This route does not exist.'], Response::HTTP_NOT_FOUND);
});
