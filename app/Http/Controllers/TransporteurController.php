<?php

namespace App\Http\Controllers;

use App\Models\Transporteur;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TransporteurController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): View|JsonResponse
    {
        $search = trim((string) $request->string('search'));

        $query = Transporteur::withCount('documents')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('tr_nom', 'like', '%'.$search.'%')
                        ->orWhere('tr_matricule', 'like', '%'.$search.'%')
                        ->orWhere('tr_chauffeur', 'like', '%'.$search.'%')
                        ->orWhere('tr_telephone', 'like', '%'.$search.'%');
                });
            })
            ->latest();

        if ($request->ajax() && $request->has('draw')) {
            $draw = (int) $request->input('draw', 1);
            $start = max(0, (int) $request->input('start', 0));
            $length = max(10, (int) $request->input('length', 10));
            $recordsTotal = (clone $query)->count();
            $recordsFiltered = $recordsTotal;
            $rows = $query->skip($start)->take($length)->get();

            $data = $rows->map(function (Transporteur $transporteur) {
                return [
                    'nom' => e($transporteur->tr_nom),
                    'matricule' => e($transporteur->tr_matricule ?: '-'),
                    'chauffeur' => e($transporteur->tr_chauffeur ?: '-'),
                    'telephone' => e($transporteur->tr_telephone ?: '-'),
                    'documents' => (int) $transporteur->documents_count,
                    'actions' => '<div class="btn-group btn-group-sm" role="group">'
                        .'<a href="'.route('transporteurs.edit', $transporteur).'" class="btn btn-xs btn-outline-primary mr-2"><i class="fas fa-pen"></i></a>'
                        .'<form action="'.route('transporteurs.destroy', $transporteur).'" method="POST" onsubmit="return confirm(\'Supprimer ce transporteur ?\');">'.csrf_field().method_field('DELETE').'<button type="submit" class="btn btn-xs btn-outline-danger"><i class="fas fa-trash"></i></button></form>'
                        .'</div>',
                ];
            })->all();

            return response()->json(compact('draw', 'recordsTotal', 'recordsFiltered', 'data'));
        }

        $transporteurs = $query->get();

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
