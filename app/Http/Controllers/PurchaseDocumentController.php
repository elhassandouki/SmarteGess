<?php

namespace App\Http\Controllers;

use App\Support\DocumentTypeRegistry;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PurchaseDocumentController extends DocumentController
{
    public function index(Request $request): View
    {
        $request->merge(['module' => DocumentTypeRegistry::MODULE_PURCHASE]);
        $baseView = parent::index($request);
        return view('achats.documents.index', $baseView->getData());
    }

    public function create(): View
    {
        request()->merge(['module' => DocumentTypeRegistry::MODULE_PURCHASE]);
        $baseView = parent::create();
        return view('achats.documents.create', $baseView->getData());
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge(['module' => DocumentTypeRegistry::MODULE_PURCHASE]);
        return parent::store($request);
    }
}
