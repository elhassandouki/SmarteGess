<?php

namespace App\Http\Controllers;

use App\Support\DocumentTypeRegistry;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SalesDocumentController extends DocumentController
{
    public function index(Request $request): View
    {
        $request->merge(['module' => DocumentTypeRegistry::MODULE_SALES]);
        $baseView = parent::index($request);
        return view('ventes.documents.index', $baseView->getData());
    }

    public function create(): View
    {
        request()->merge(['module' => DocumentTypeRegistry::MODULE_SALES]);
        $baseView = parent::create();
        return view('ventes.documents.create', $baseView->getData());
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge(['module' => DocumentTypeRegistry::MODULE_SALES]);
        return parent::store($request);
    }
}
