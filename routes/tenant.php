<?php

declare(strict_types=1);

use App\Http\Controllers\BankAccountController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\EventUserController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\PINCodeController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use App\Models\BankAccount;
use App\Models\Category;
use App\Models\Event;
use App\Models\EventUser;
use App\Models\Item;
use App\Models\Transaction;
use App\Models\User;
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
    // 'throttle:open',
])->group(function () {
    Route::post('register', RegisterController::class);
    Route::post('pincode/{user}', [PINCodeController::class, 'activate'])->name('pin.activate');
    Route::get('pincode' , [PINCodeController::class, 'confirm'])->name('pin.confirm');
    Route::put('pincode/{user}', [PINCodeController::class, 'reset'])->name('pin.reset');
    Route::post('login', LoginController::class);
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
    Route::get('users', [UserController::class, 'index'])->can('viewAny', User::class);
    Route::get('users/{user}', [UserController::class, 'show'])->can('view', 'user');
    Route::patch('users/{user}', [UserController::class, 'update'])->can('update', 'user');
    Route::delete('users/{user}', [UserController::class, 'destroy'])->can('delete', 'user');
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
    Route::post('users', [UserController::class, 'seedManager'])->can('seedManager', User::class);
    // This route is to activate or deactivate a user. The user's token is revoked upon deactivation.
    Route::put('users/{user}', [UserController::class, 'toggleIsActive'])->can('toggleIsActive', 'user');
    // This route is used to access the user events.
    Route::get('users/{user}/events', [UserController::class, 'events'])->can('viewEvents', 'user');

    Route::get('events', [EventController::class, 'index'])->can('viewAny', Event::class);
    Route::post('events', [EventController::class, 'store'])->can('create', Event::class);
    Route::get('events/{event}', [EventController::class, 'show'])->can('view', 'event');
    Route::patch('events/{event}', [EventController::class, 'update'])->can('update', 'event');
    Route::delete('events/{event}', [EventController::class, 'destroy'])->can('delete', 'event');
    // This route is used to access the event categories.
    Route::get('events/{event}/categories', [EventController::class, 'categories'])->can('viewCategories', 'event');
    // This route is used to access the event users.
    // This route is used to access the event transactions.
    Route::get('events/{event}/transactions', [EventController::class, 'transactions'])->can('viewTransactions', 'event');
    Route::get('events/{event}/users', [EventController::class, 'users'])->can('viewUsers', 'event');
    
    Route::get('bankaccounts', [BankAccountController::class, 'index'])->can('viewAny', BankAccount::class);
    Route::post('bankaccounts', [BankAccountController::class, 'store'])->can('create', BankAccount::class);
    Route::get('bankaccounts/{bankAccount}', [BankAccountController::class, 'show'])->can('view', 'bankAccount');
    Route::patch('bankaccounts/{bankAccount}', [BankAccountController::class, 'update'])->can('update', 'bankAccount');
    Route::delete('bankaccounts/{bankAccount}', [BankAccountController::class, 'destroy'])->can('delete', 'bankAccount');

    // Authorization is handled in controller.
    Route::post('events/{event}/users', [EventUserController::class, 'seedSeller']);
    Route::put('events/{event}/users/{user}', [EventUserController::class, 'upsert']);
    // Detach is within scope of manager.
    Route::delete('events/{event}/users/{user}', [EventUserController::class, 'destroy']); 
    // This route is used to access the user transactions executed during an event.
    Route::get('events/{event}/users/{user}/transactions', [EventUserController::class, 'transactions']);

    Route::get('categories', [CategoryController::class, 'index'])->can('viewAny', Category::class);
    Route::post('events/{event}/categories', [CategoryController::class, 'store'])->can('create', [Category::class, 'event']);
    Route::get('categories/{category}', [CategoryController::class, 'show'])->can('view', 'category');
    Route::patch('categories/{category}', [CategoryController::class, 'update'])->can('update', 'category');
    Route::delete('categories/{category}', [CategoryController::class, 'destroy'])->can('delete', 'category');
    // This route is used to access the category items.
    Route::get('categories/{category}/items', [CategoryController::class, 'items'])->can('viewItems', 'category');

    Route::get('items', [ItemController::class, 'index'])->can('viewAny', Item::class);
    Route::post('categories/{category}/items', [ItemController::class, 'store'])->can('create', [Item::class, 'category']);
    Route::get('items/{item}', [ItemController::class, 'show'])->can('view', 'item');
    Route::patch('items/{item}', [ItemController::class, 'update'])->can('update', 'item');
    Route::delete('items/{item}', [ItemController::class, 'destroy'])->can('delete', 'item');

    // This route is used to access the item transactions.
    // Route::get('items/{item}/transactions', [ItemController::class, 'transactions'])->can('viewTransactions', 'item'));

    Route::get('transactions', [TransactionController::class, 'index'])->can('viewAny', Transaction::class);
    // This route also inserts the pivot table entries.
    Route::post('events/{event}/transactions', [TransactionController::class, 'store'])->can('create', [Transaction::class, 'event']); 
    Route::get('transactions/{transaction}', [TransactionController::class, 'show'])->can('view', 'transaction');
     // This route is used to modify the status of a transaction.
    Route::put('transactions/{transaction}', [TransactionController::class, 'toggleStatus'])->can('toggleStatus', 'transaction');
    Route::delete('transactions/{transaction}', [TransactionController::class, 'destroy'])->can('delete', 'transaction');
    // This route is used to access the transaction items.
    Route::get('transactions/{transaction}/items', [TransactionController::class, 'items'])->can('viewItems', 'transaction');

    // This route is used to access the user transactions.
    // Route::get('users/{user}/transactions', [UserController::class, 'transactions'])->middleware('ability:admin,manager,seller');
});


Route::fallback(function () {
    // xdebug_info(); // Comment out in prod
    return response()->json(['message' => 'This route does not exist.'], Response::HTTP_NOT_FOUND);
});
