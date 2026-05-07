<?php

namespace App\Http\Controllers;

use App\Support\DocumentTypeRegistry;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SalesDocumentController extends DocumentController
{
    public function index(Request $request): View
    {
        $request->merge(['module' => DocumentTypeRegistry::MODULE_SALES]);
        return parent::index($request);
    }
}
