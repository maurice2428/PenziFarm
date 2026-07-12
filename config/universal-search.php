<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Universal Search
    |--------------------------------------------------------------------------
    |
    | This search sits in the Filament topbar and searches:
    | - Every discovered Filament Resource
    | - Resource navigation destinations
    | - Custom Filament Pages
    |
    | It respects each Resource's Filament authorization methods.
    |
    */

    'enabled' => true,

    'minimum_query_length' => 2,

    'debounce' => 400,

    'per_resource_limit' => 5,

    'total_result_limit' => 40,

    /*
     * Directories used for automatic discovery.
     */
    'resource_directories' => [
        app_path('Filament/Resources'),
    ],

    'page_directories' => [
        app_path('Filament/Pages'),
    ],

    /*
     * Resource and page classes that should never appear.
     *
     * Use complete class names as strings:
     *
     * 'App\\Filament\\Resources\\ExampleResource',
     */
    'excluded_resources' => [
        //
    ],

    'excluded_pages' => [
        //
    ],

    /*
     * Common model columns automatically considered searchable when a
     * Resource has not configured Filament's native global search fields.
     */
    'common_search_columns' => [
        'id',
        'name',
        'full_name',
        'first_name',
        'middle_name',
        'last_name',
        'title',
        'label',
        'code',
        'number',
        'reference',
        'description',
        'email',
        'phone',
        'alternate_phone',
        'status',

        'employee_number',
        'id_passport_number',
        'kra_pin',
        'nssf_number',
        'nhif_sha_number',

        'tag_number',
        'tag_name',
        'species',

        'invoice_number',
        'receipt_number',
        'payment_reference',
        'purchase_order_number',
        'supplier_invoice_number',

        'sku',
        'barcode',
        'batch_number',
        'serial_number',

        'account_number',
        'account_code',
        'journal_number',
        'entry_number',

        'project_number',
        'asset_number',
        'registration_number',
    ],

    /*
     * Attributes used as a fallback record title, in priority order.
     */
    'title_candidates' => [
        'full_name',
        'name',
        'title',
        'employee_number',
        'tag_number',
        'tag_name',
        'invoice_number',
        'purchase_order_number',
        'receipt_number',
        'reference',
        'code',
        'number',
        'id',
    ],

    /*
     * Attributes displayed below a result when the Resource has not supplied
     * getGlobalSearchResultDetails().
     */
    'detail_candidates' => [
        'status' => 'Status',
        'email' => 'Email',
        'phone' => 'Phone',
        'employee_number' => 'Employee No.',
        'tag_number' => 'Tag No.',
        'invoice_number' => 'Invoice No.',
        'reference' => 'Reference',
        'code' => 'Code',
    ],

    /*
     * Optional Resource-specific tuning.
     *
     * The manager still auto-discovers all Resources. Add entries here only
     * when a Resource needs relationship fields or a custom title.
     */
    'resource_overrides' => [
        'App\\Filament\\Resources\\HR\\EmployeeResource' => [
            'search' => [
                'employee_number',
                'full_name',
                'first_name',
                'middle_name',
                'last_name',
                'id_passport_number',
                'kra_pin',
                'nssf_number',
                'nhif_sha_number',
                'phone',
                'alternate_phone',
                'email',
                'department.name',
                'jobTitle.name',
                'manager.full_name',
                'work_station',
                'status',
            ],

            'title' => 'full_name',

            'details' => [
                'Employee No.' => 'employee_number',
                'Department' => 'department.name',
                'Job Title' => 'jobTitle.name',
                'Phone' => 'phone',
                'Status' => 'status',
            ],

            'with' => [
                'department',
                'jobTitle',
                'manager',
            ],
        ],
    ],

    /*
     * Extra aliases for custom Filament Pages.
     *
     * Example:
     * 'App\\Filament\\Pages\\Accounting\\TrialBalance' => [
     *     'trial balance',
     *     'accounts report',
     * ],
     */
    'page_aliases' => [
        //
    ],
];
