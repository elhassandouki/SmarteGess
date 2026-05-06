<?php

namespace App\Http\Controllers;

use App\Models\Transporteur;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TransporteurController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->string('search'));

        $transporteurs = Transporteur::withCount('documents')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('tr_nom', 'like', '%'.$search.'%')
                        ->orWhere('tr_matricule', 'like', '%'.$search.'%')
                        ->orWhere('tr_chauffeur', 'like', '%'.$search.'%')
                        ->orWhere('tr_telephone', 'like', '%'.$search.'%');
                });
            })
            ->latest()
            ->get();

        return view('transporteurs.index', [
            'transporteurs' => $transporteurs,
            'filters' => ['search' => $search],
        ]);
    }

    public function create(): View
    {
        return view('transporteurs.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tr_nom' => ['required', 'string', 'max:255'],
            'tr_matricule' => ['nullable', 'string', 'max:100'],
            'tr_chauffeur' => ['nullable', 'string', 'max:255'],
            'tr_telephone' => ['nullable', 'string', 'max:50'],
        ]);

        Transporteur::create($data);

        return redirect()->route('transporteurs.index')->with('success', 'Transporteur cree avec succes.');
    }

    public function edit(Transporteur $transporteur): View
    {
        return view('transporteurs.edit', compact('transporteur'));
    }

    public function update(Request $request, Transporteur $transporteur): RedirectResponse
    {
        $data = $request->validate([
            'tr_nom' => ['required', 'string', 'max:255'],
            'tr_matricule' => ['nullable', 'string', 'max:100'],
            'tr_chauffeur' => ['nullable', 'string', 'max:255'],
            'tr_telephone' => ['nullable', 'string', 'max:50'],
        ]);

        $transporteur->update($data);

        return redirect()->route('transporteurs.index')->with('success', 'Transporteur mis a jour avec succes.');
    }

    public function destroy(Transporteur $transporteur): RedirectResponse
    {
        if ($transporteur->documents()->exists()) {
            return redirect()->route('transporteurs.index')->with('error', 'Impossible de supprimer un transporteur lie a des documents.');
        }

        $transporteur->delete();

        return redirect()->route('transporteurs.index')->with('success', 'Transporteur supprime avec succes.');
    }
}
