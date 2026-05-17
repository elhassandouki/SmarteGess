<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanySetting extends Model
{
    use HasFactory;
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'company_name',
        'company_ice',
        'company_if',
        'company_phone',
        'company_email',
        'company_address',
        'logo_path',
        'invoice_prefix',
        'invoice_footer',
        'primary_color',
    ];
}
