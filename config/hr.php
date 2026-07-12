<?php

return [
    'employee_number' => [
        'setting_key' => 'hr.employee_number_prefix',
        'suffix' => 'STF',
        'padding' => 4,
    ],

    'paye' => [
        'personal_relief_monthly' => (float) env('HR_PAYE_PERSONAL_RELIEF_MONTHLY', 2400),
        'insurance_relief_rate' => (float) env('HR_INSURANCE_RELIEF_RATE', 0.15),
        'insurance_relief_monthly_cap' => (float) env('HR_INSURANCE_RELIEF_MONTHLY_CAP', 5000),
        'registered_pension_monthly_cap' => (float) env('HR_PENSION_MONTHLY_CAP', 30000),
        'post_retirement_medical_monthly_cap' => (float) env('HR_PRMF_MONTHLY_CAP', 15000),
        'mortgage_interest_monthly_cap' => (float) env('HR_MORTGAGE_MONTHLY_CAP', 30000),
    ],

    'statutory' => [
        'shif_rate' => (float) env('HR_SHIF_RATE', 0.0275),
        'shif_minimum_monthly' => (float) env('HR_SHIF_MINIMUM_MONTHLY', 300),
        'affordable_housing_levy_rate' => (float) env('HR_AHL_RATE', 0.015),
    ],
];
