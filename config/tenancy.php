<?php

return [
    'strategy' => env('TENANCY_STRATEGY', 'single_database'),
    'tenant_header' => env('TENANT_HEADER', 'X-Tenant-Id'),
    'enforce_tenant_scope' => env('ENFORCE_TENANT_SCOPE', false),
];
