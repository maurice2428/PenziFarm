<?php

use App\Models\AuditLog;
use App\Models\AuditSession;
use App\Models\AuditSetting;
use Illuminate\Notifications\DatabaseNotification;
use Laravel\Sanctum\PersonalAccessToken;

return [
    'enabled' => env('AUDIT_ENABLED', true),

    /*
     * |--------------------------------------------------------------------------
     * | Request / Journey Tracking
     * |--------------------------------------------------------------------------
     */
    'track_models' => env('AUDIT_TRACK_MODELS', true),
    'track_page_views' => env('AUDIT_TRACK_PAGE_VIEWS', true),
    'track_livewire_requests' => env('AUDIT_TRACK_LIVEWIRE_REQUESTS', false),
    'track_failed_requests' => env('AUDIT_TRACK_FAILED_REQUESTS', true),
    'session_lifetime_minutes' => env('AUDIT_SESSION_LIFETIME', 120),
    'ignored_paths' => [
        '_debugbar/*',
        'telescope/*',
        'horizon/*',
        'livewire/*',
        'build/*',
        'storage/*',
        'favicon.ico',
        '*.js',
        '*.css',
        '*.map',
        '*.png',
        '*.jpg',
        '*.jpeg',
        '*.webp',
        '*.svg',
        '*.ico',
    ],
    'ignored_route_names' => [
        'livewire.update',
        'filament.livewire.update',
    ],

    /*
     * |--------------------------------------------------------------------------
     * | Model Tracking
     * |--------------------------------------------------------------------------
     */
    'track_models' => env('AUDIT_TRACK_MODELS', true),
    'excluded_models' => [
        AuditLog::class,
        AuditSession::class,
        AuditSetting::class,
        DatabaseNotification::class,
        PersonalAccessToken::class,
    ],
    'excluded_fields' => [
        'password',
        'password_confirmation',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'api_token',
        'access_token',
        'refresh_token',
        'secret',
        'token',
        'private_key',
        'public_key',
        'authorization',
        'session_id',
        'laravel_session',
        'created_at',
        'updated_at',
        'deleted_at',
    ],

    /*
     * |--------------------------------------------------------------------------
     * | Module Labels
     * |--------------------------------------------------------------------------
     */
    'model_modules' => [
        'Animal' => 'Livestock',
        'AnimalWeight' => 'Livestock',
        'Breed' => 'Livestock',
        'BreedingBatch' => 'Breeding',
        'BreedingRecord' => 'Breeding',
        'HealthProduct' => 'Veterinary',
        'HealthAdministration' => 'Veterinary',
        'AnimalTreatment' => 'Veterinary',
        'AnimalFeeding' => 'Feeding',
        'AnimalFeedingItem' => 'Feeding',
        'InventoryItem' => 'Inventory',
        'StockMovement' => 'Inventory',
        'StockAdjustment' => 'Inventory',
        'StockAdjustmentItem' => 'Inventory',
        'PurchaseOrder' => 'Procurement',
        'GoodsReceivedNote' => 'Procurement',
        'Supplier' => 'Procurement',
        'SupplierPayment' => 'Procurement',
        'Sale' => 'Sales',
        'SalesInvoice' => 'Sales',
        'SalesPayment' => 'Sales',
        'Customer' => 'Sales',
        'Employee' => 'Human Resource',
        'Payroll' => 'Payroll',
        'Attendance' => 'Human Resource',
        'FarmAsset' => 'Asset Valuation',
        'AssetValuation' => 'Asset Valuation',
        'AssetMaintenance' => 'Asset Valuation',
        'CropCatalog' => 'Crop Farming',
        'CropSeason' => 'Crop Farming',
        'CropActivity' => 'Crop Farming',
        'CropHarvestRecord' => 'Crop Farming',
        'NurseryBatch' => 'Crop Farming',
        'User' => 'Administration',
        'Role' => 'Administration',
        'Permission' => 'Administration',
    ],

    /*
     * |--------------------------------------------------------------------------
     * | High Risk Events
     * |--------------------------------------------------------------------------
     */
    'high_risk_events' => [
        'deleted',
        'force_deleted',
        'failed_login',
        'permission_changed',
        'role_changed',
        'stock_adjustment',
        'payment_deleted',
        'payment_updated',
        'backdated_transaction',
        'approved',
        'rejected',
        'cancelled',
    ],
];
