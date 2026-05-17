<?php

namespace App\Services\SaaS;

use Illuminate\Support\Facades\DB;

class InvoiceNumberingService
{
    public function nextNumber(int $tenantId, string $documentCode, string $prefix = 'FAC'): string
    {
        $year = (int) now()->format('Y');

        return DB::transaction(function () use ($tenantId, $documentCode, $year, $prefix): string {
            $row = DB::table('invoice_sequences')
                ->where('tenant_id', $tenantId)
                ->where('document_code', $documentCode)
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            if (!$row) {
                DB::table('invoice_sequences')->insert([
                    'tenant_id' => $tenantId,
                    'document_code' => $documentCode,
                    'year' => $year,
                    'last_number' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $next = 1;
            } else {
                $next = ((int) $row->last_number) + 1;
                DB::table('invoice_sequences')
                    ->where('id', $row->id)
                    ->update(['last_number' => $next, 'updated_at' => now()]);
            }

            return sprintf('%s-%s-%05d', strtoupper($prefix), $year, $next);
        });
    }
}
