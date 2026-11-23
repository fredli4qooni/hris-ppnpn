<?php

return [
    /*
    |--------------------------------------------------------------------------
    | HRIS Contract Lifecycle & Expiry Alerts (FR-601, FR-602, FR-603, FR-604)
    |--------------------------------------------------------------------------
    |
    | Configuration for employee SPK contract lifecycle management and daily
    | warning thresholds before the contract expiration date.
    |
    */
    'contracts' => [
        // Days before contract expiration to trigger visual & notification alerts
        'alert_thresholds' => [90, 60, 30, 7],

        // Critical threshold in days requiring urgent administrative follow-up
        'critical_threshold' => 7,

        // Notification channels (database, log)
        'channels' => ['database', 'log'],
    ],
];
