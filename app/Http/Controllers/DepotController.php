<?php

namespace App\Http\Controllers;

use App\Models\Depot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DepotController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): View|JsonResponse
    {
        $search = trim((string) $request->string('search'));

        $query = Depot::query()
            ->withCount('stocks')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('code_depot', 'like', '%'.$search.'%')
                        ->orWhere('intitule', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('intitule');

        if ($request->ajax() && $request->has('draw')) {
            $draw = (int) $request->input('draw', 1);
            $start = max(0, (int) $request->input('start', 0));
            $length = max(10, (int) $request->input('length', 10));
            $recordsTotal = (clone $query)->count();
            $recordsFiltered = $recordsTotal;
            $rows = $query->skip($start)->take($length)->get();

            $data = $rows->map(function (Depot $depot) {
                return [
                    'code' => e($depot->code_depot),
                    'intitule' => e($depot->intitule),
                    'stocks_count' => (int) $depot->stocks_count,
                    'actions' => '<div class="btn-group btn-group-sm" role="group">'
                        .'<a href="'.route('depots.edit', $depot).'" class="btn btn-xs btn-outline-primary mr-2"><i class="fas fa-pen"></i></a>'
                        .'<form action="'.route('depots.destroy', $depot).'" method="POST" onsubmit="return confirm(\'Supprimer ce depot ?\');">'.csrf_field().method_field('DELETE').'<button type="submit" class="btn btn-xs btn-outline-danger"><i class="fas fa-trash"></i></button></form>'
                        .'</div>',
                ];
            })->all();

            return response()->json(compact('draw', 'recordsTotal', 'recordsFiltered', 'data'));
        }

        $depots = $query->get();

        return view('depots.index', [
            'depots' => $depots,
            'filters' => ['search' => $search],
        ]);
    }

    public function create(): View
    {
        return view('depots.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateDepot($request);

        Depot::create($data);

        return redirect()->route('depots.index')->with('success', 'Depot cree avec succes.');
    }

    public function edit(Depot $depot): View
    {
        return view('depots.edit', compact('depot'));
    }

    public function update(Request $request, Depot $depot): RedirectResponse
    {
        $data = $this->validateDepot($request, $depot->id);

        $depot->update($data);

        return redirect()->route('depots.index')->with('success', 'Depot mis a jour avec succes.');
    }

    public function destroy(Depot $depot): RedirectResponse
    {
        if ($depot->stocks()->exists()) {
            return redirect()->route('depots.index')->with('error', 'Impossible de supprimer un depot qui contient du stock.');
        }

        $depot->delete();

        return redirect()->route('depots.index')->with('success', 'Depot supprime avec succes.');
    }

    protected function validateDepot(Request $request, ?int $depotId = null): array
    {
        return $request->validate([
            'code_depot' => ['required', 'string', 'max:50', Rule::unique('f_depots', 'code_depot')->ignore($depotId)],
            'intitule' => ['required', 'string', 'max:255'],
        ]);
    }
}
