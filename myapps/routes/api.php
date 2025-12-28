<?php

use App\Http\Controllers\API\SSOTController;
use App\Http\Controllers\Auth\SSOController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - MyApps KEDA
|--------------------------------------------------------------------------
|
| SSO (Single Sign-On) & SSOT (Single Source of Truth) API Routes
|
*/

// Public routes
Route::prefix('v1')->group(function () {
    
    // SSO Authentication
    Route::prefix('sso')->group(function () {
        Route::post('/token', [SSOController::class, 'generateToken'])->name('api.sso.token');
    });
    
});

// Protected routes (require Sanctum authentication)
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    
    // SSO Management
    Route::prefix('sso')->group(function () {
        Route::get('/verify', [SSOController::class, 'verifyToken'])->name('api.sso.verify');
        Route::post('/revoke', [SSOController::class, 'revokeToken'])->name('api.sso.revoke');
        Route::post('/revoke-all', [SSOController::class, 'revokeAllTokens'])->name('api.sso.revoke-all');
        Route::get('/tokens', [SSOController::class, 'getActiveTokens'])->name('api.sso.tokens');
    });
    
    // SSOT (Single Source of Truth) - Staff Data
    Route::prefix('staf')->group(function () {
        Route::get('/{no_kp}', [SSOTController::class, 'getStafByNoKp'])->name('api.staf.show');
        Route::get('/', [SSOTController::class, 'getAllStaf'])->name('api.staf.index');
        Route::get('/search', [SSOTController::class, 'searchStaf'])->name('api.staf.search');
    });
    
    // Authenticated user info
    Route::get('/user', function (Request $request) {
        $staf = $request->user();
        return response()->json([
            'success' => true,
            'data' => [
                'id_staf' => $staf->id_staf,
                'no_staf' => $staf->no_staf,
                'no_kp' => $staf->no_kp,
                'nama' => $staf->nama,
                'emel' => $staf->emel,
                'telefon' => $staf->telefon,
                'gambar_url' => $staf->gambar_url,
                'jawatan' => $staf->jawatan?->jawatan,
                'gred' => $staf->gred?->gred,
                'bahagian' => $staf->bahagian?->bahagian,
                'status' => $staf->status?->status,
            ],
        ]);
    })->name('api.user');
});

// Health check
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'MyApps KEDA API',
        'version' => '1.0.0',
        'timestamp' => now()->toISOString(),
    ]);
})->name('api.health');

