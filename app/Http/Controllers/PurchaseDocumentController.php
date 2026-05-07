<?php

namespace App\Http\Controllers;

use App\Support\DocumentTypeRegistry;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PurchaseDocumentController extends DocumentController
{
    public function index(Request $request): View
    {
        $request->merge(['module' => DocumentTypeRegistry::MODULE_PURCHASE]);
        return parent::index($request);
    }
}
