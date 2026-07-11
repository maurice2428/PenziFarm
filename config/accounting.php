<?php

return [
    'auto_posting_enabled' => env('ACCOUNTING_AUTO_POSTING_ENABLED', true),
    'currency' => env('ACCOUNTING_CURRENCY', 'KES'),
    'country' => 'KE',
    'require_manual_journal_approval' => true,
    'allow_negative_stock_value' => false,
    'tax' => [
        'vat_registration_enabled' => env('ACCOUNTING_VAT_ENABLED', false),
        'withholding_vat_agent' => env('ACCOUNTING_WH_VAT_AGENT', false),
        'turnover_tax_enabled' => env('ACCOUNTING_TOT_ENABLED', false),
        'etims_enabled' => env('ACCOUNTING_ETIMS_ENABLED', false),
    ],
];
