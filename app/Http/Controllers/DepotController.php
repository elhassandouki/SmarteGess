<?php

namespace App\Http\Controllers;

use App\Models\Depot;
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

    public function index(Request $request): View
    {
        $search = trim((string) $request->string('search'));

        $depots = Depot::query()
            ->withCount('stocks')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('code_depot', 'like', '%'.$search.'%')
                        ->orWhere('intitule', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('intitule')
            ->get();

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
