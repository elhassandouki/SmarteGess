<?php

namespace App\Support\Tenancy;

use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Http\Request;

class TenantResolver
{
    public function __construct(
        private readonly AuthFactory $auth,
        private readonly Request $request
    ) {
    }

    public function tenantId(): ?int
    {
        $user = $this->auth->guard()->user();
        if ($user && isset($user->tenant_id) && $user->tenant_id !== null) {
            return (int) $user->tenant_id;
        }

        $header = $this->request->header(config('tenancy.tenant_header', 'X-Tenant-Id'));
        if (is_numeric($header)) {
            return (int) $header;
        }

        return null;
    }
}
