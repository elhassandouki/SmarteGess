<?php

namespace App\Http\Controllers;

use App\Models\Tax;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TaxController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): View|JsonResponse
    {
        $search = trim((string) $request->string('search'));

        $query = Tax::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('code_taxe', 'like', '%'.$search.'%')
                        ->orWhere('libelle', 'like', '%'.$search.'%');
                });
            });

        if ($request->ajax() && $request->has('draw')) {
            $draw = (int) $request->input('draw', 1);
            $start = max(0, (int) $request->input('start', 0));
            $length = max(10, (int) $request->input('length', 10));
            $recordsTotal = (clone $query)->count();
            $recordsFiltered = $recordsTotal;
            $rows = $query->orderByDesc('id')->skip($start)->take($length)->get();

            $data = $rows->map(function (Tax $tax) {
                return [
                    'code_taxe' => e($tax->code_taxe),
                    'libelle' => e($tax->libelle),
                    'taux' => $tax->taux . '%',
                    'actions' => '<div class="btn-group btn-group-sm" role="group">'
                        .'<a href="'.route('taxes.show', $tax).'" class="btn btn-xs btn-outline-secondary mr-2"><i class="fas fa-eye"></i></a>'
                        .'<a href="'.route('taxes.edit', $tax).'" class="btn btn-xs btn-outline-primary mr-2"><i class="fas fa-pen"></i></a>'
                        .'<form action="'.route('taxes.destroy', $tax).'" method="POST" onsubmit="return confirm(\'Supprimer cette taxe ?\');" style="display:inline;">'.csrf_field().method_field('DELETE').'<button type="submit" class="btn btn-xs btn-outline-danger"><i class="fas fa-trash"></i></button></form>'
                        .'</div>',
                ];
            })->all();

            return response()->json(compact('draw', 'recordsTotal', 'recordsFiltered', 'data'));
        }

        $taxes = $query->latest()->get();

        return view('taxes.index', [
            'taxes' => $taxes,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function create(): View
    {
        return view('taxes.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code_taxe' => ['required', 'string', 'max:20', 'unique:f_taxes,code_taxe'],
            'libelle' => ['required', 'string', 'max:100'],
            'taux' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        Tax::create($data);

        return redirect()->route('taxes.index')->with('success', 'Taxe creee avec succes.');
    }

    public function show(Tax $tax): View
    {
        return view('taxes.show', compact('tax'));
    }

    public function edit(Tax $tax): View
    {
        return view('taxes.edit', compact('tax'));
    }

    public function update(Request $request, Tax $tax): RedirectResponse
    {
        $data = $request->validate([
            'code_taxe' => ['required', 'string', 'max:20', 'unique:f_taxes,code_taxe,'.$tax->id],
            'libelle' => ['required', 'string', 'max:100'],
            'taux' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $tax->update($data);

        return redirect()->route('taxes.show', $tax)->with('success', 'Taxe mise a jour avec succes.');
    }

    public function destroy(Tax $tax): RedirectResponse
    {
        $tax->delete();

        return redirect()->route('taxes.index')->with('success', 'Taxe supprimee avec succes.');
    }
}
