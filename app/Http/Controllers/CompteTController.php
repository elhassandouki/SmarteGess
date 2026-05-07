<?php

namespace App\Http\Controllers;

use App\Models\CompteT;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CompteTController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->string('search'));
        $type = trim((string) $request->string('type'));
        $entity = trim((string) $request->string('entity'));

        $tiers = CompteT::withCount(['documents', 'reglements'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('ct_num', 'like', '%'.$search.'%')
                        ->orWhere('code_tiers', 'like', '%'.$search.'%')
                        ->orWhere('ct_intitule', 'like', '%'.$search.'%')
                        ->orWhere('ct_telephone', 'like', '%'.$search.'%')
                        ->orWhere('ct_ice', 'like', '%'.$search.'%');
                });
            })
            ->when($type !== '', fn ($query) => $query->where('ct_type', $type))
            ->orderBy('ct_intitule')
            ->get();

        return view('tiers.index', [
            'tiers' => $tiers,
            'entity' => $entity,
            'filters' => [
                'search' => $search,
                'type' => $type,
            ],
        ]);
    }

    public function clients(Request $request): View
    {
        $request->merge(['type' => 'client', 'entity' => 'clients']);
        return $this->index($request);
    }

    public function suppliers(Request $request): View
    {
        $request->merge(['type' => 'fournisseur', 'entity' => 'suppliers']);
        return $this->index($request);
    }

    public function create(): View
    {
        return view('tiers.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateTier($request);

        $tier = CompteT::create($data);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Tiers cree avec succes.',
                'tier' => $tier,
            ]);
        }

        return redirect()->route('tiers.index')->with('success', 'Tiers cree avec succes.');
    }

    public function edit(CompteT $tier): View
    {
        return view('tiers.edit', compact('tier'));
    }

    public function update(Request $request, CompteT $tier): RedirectResponse
    {
        $data = $this->validateTier($request, $tier->id);

        $tier->update($data);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Tiers mis a jour avec succes.',
                'tier' => $tier->fresh(),
            ]);
        }

        return redirect()->route('tiers.index')->with('success', 'Tiers mis a jour avec succes.');
    }

    public function destroy(CompteT $tier): RedirectResponse
    {
        if ($tier->documents()->exists() || $tier->reglements()->exists()) {
            if (request()->expectsJson()) {
                return response()->json([
                    'message' => 'Impossible de supprimer un tiers lie a des documents ou reglements.',
                ], 422);
            }

            return redirect()->route('tiers.index')->with('error', 'Impossible de supprimer un tiers lie a des documents ou reglements.');
        }

        $tier->delete();

        if (request()->expectsJson()) {
            return response()->json([
                'message' => 'Tiers supprime avec succes.',
            ]);
        }

        return redirect()->route('tiers.index')->with('success', 'Tiers supprime avec succes.');
    }

    protected function validateTier(Request $request, ?int $tierId = null): array
    {
        $data = $request->validate([
            'ct_num' => ['required', 'string', 'max:100', Rule::unique('f_comptet', 'ct_num')->ignore($tierId)],
            'code_tiers' => ['nullable', 'string', 'max:100', Rule::unique('f_comptet', 'code_tiers')->ignore($tierId)],
            'ct_intitule' => ['required', 'string', 'max:255'],
            'ct_type' => ['required', Rule::in(['client', 'fournisseur', 'prospect'])],
            'ct_ice' => ['nullable', 'string', 'max:15', Rule::unique('f_comptet', 'ct_ice')->ignore($tierId)],
            'ct_if' => ['nullable', 'string', 'max:20'],
            'ct_encours_max' => ['nullable', 'numeric', 'min:0'],
            'ct_delai_paiement' => ['nullable', 'integer', 'min:0'],
            'ct_telephone' => ['nullable', 'string', 'max:50'],
            'ct_adresse' => ['nullable', 'string', 'max:255'],
        ]);

        $data['code_tiers'] = $data['code_tiers'] ?? $data['ct_num'];
        $data['ct_encours_max'] = $data['ct_encours_max'] ?? 0;
        $data['ct_delai_paiement'] = $data['ct_delai_paiement'] ?? 0;
        $data['entity_type'] = match ($data['ct_type']) {
            'fournisseur' => 'supplier',
            'prospect' => 'prospect',
            default => 'client',
        };

        return $data;
    }
}
