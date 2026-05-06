<?php

namespace App\Http\Controllers;

use App\Models\Family;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FamilyController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->string('search'));
        $minArticles = $request->integer('min_articles');

        $families = Family::withCount('articles')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('fa_code', 'like', '%'.$search.'%')
                        ->orWhere('fa_intitule', 'like', '%'.$search.'%');
                });
            })
            ->when($minArticles > 0, fn ($query) => $query->having('articles_count', '>=', $minArticles))
            ->latest()
            ->get();

        return view('families.index', [
            'families' => $families,
            'filters' => [
                'search' => $search,
                'min_articles' => $minArticles,
            ],
        ]);
    }

    public function create(): View
    {
        return view('families.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'fa_code' => ['required', 'string', 'max:50', 'unique:f_familles,fa_code'],
            'fa_intitule' => ['required', 'string', 'max:255'],
        ]);

        Family::create($data);

        return redirect()->route('families.index')->with('success', 'Famille creee avec succes.');
    }

    public function edit(Family $family): View
    {
        return view('families.edit', compact('family'));
    }

    public function update(Request $request, Family $family): RedirectResponse
    {
        $data = $request->validate([
            'fa_code' => ['required', 'string', 'max:50', 'unique:f_familles,fa_code,'.$family->id],
            'fa_intitule' => ['required', 'string', 'max:255'],
        ]);

        $family->update($data);

        return redirect()->route('families.index')->with('success', 'Famille mise a jour avec succes.');
    }

    public function destroy(Family $family): RedirectResponse
    {
        if ($family->articles()->exists()) {
            return redirect()->route('families.index')->with('error', 'Impossible de supprimer une famille qui contient des articles.');
        }

        $family->delete();

        return redirect()->route('families.index')->with('success', 'Famille supprimee avec succes.');
    }
}
