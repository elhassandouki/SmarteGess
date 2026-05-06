<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Family;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ArticleController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->string('search'));
        $familyId = $request->integer('family_id') ?: null;
        $lowOnly = $request->boolean('low_only');

        $articles = Article::with('family')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('code_article', 'like', '%'.$search.'%')
                        ->orWhere('ar_ref', 'like', '%'.$search.'%')
                        ->orWhere('ar_design', 'like', '%'.$search.'%')
                        ->orWhere('ar_code_barre', 'like', '%'.$search.'%');
                });
            })
            ->when($familyId, fn ($query) => $query->where('family_id', $familyId))
            ->when($lowOnly, fn ($query) => $query->whereColumn('ar_stock_actuel', '<=', 'ar_stock_min'))
            ->latest()
            ->get();

        return view('articles.index', [
            'articles' => $articles,
            'families' => Family::orderBy('fa_intitule')->get(),
            'filters' => [
                'search' => $search,
                'family_id' => $familyId,
                'low_only' => $lowOnly,
            ],
        ]);
    }

    public function create(): View
    {
        $families = Family::orderBy('fa_intitule')->get();

        return view('articles.create', compact('families'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateArticle($request);

        Article::create($data);

        return redirect()->route('articles.index')->with('success', 'Article cree avec succes.');
    }

    public function edit(Article $article): View
    {
        $families = Family::orderBy('fa_intitule')->get();

        return view('articles.edit', compact('article', 'families'));
    }

    public function update(Request $request, Article $article): RedirectResponse
    {
        $data = $this->validateArticle($request, $article->id);

        $article->update($data);

        return redirect()->route('articles.index')->with('success', 'Article mis a jour avec succes.');
    }

    public function destroy(Article $article): RedirectResponse
    {
        if ($article->documentLines()->exists()) {
            return redirect()->route('articles.index')->with('error', 'Impossible de supprimer un article deja utilise dans un document.');
        }

        $article->delete();

        return redirect()->route('articles.index')->with('success', 'Article supprime avec succes.');
    }

    protected function validateArticle(Request $request, ?int $articleId = null): array
    {
        $data = $request->validate([
            'ar_ref' => ['required', 'string', 'max:100', 'unique:f_articles,ar_ref,'.($articleId ?? 'NULL').',id'],
            'code_article' => ['nullable', 'string', 'max:100', Rule::unique('f_articles', 'code_article')->ignore($articleId)],
            'ar_design' => ['required', 'string', 'max:255'],
            'ar_code_barre' => ['nullable', 'string', 'max:255', Rule::unique('f_articles', 'ar_code_barre')->ignore($articleId)],
            'family_id' => ['nullable', 'exists:f_familles,id'],
            'ar_prix_achat' => ['required', 'numeric', 'min:0'],
            'ar_prix_vente' => ['required', 'numeric', 'min:0'],
            'ar_prix_revient' => ['nullable', 'numeric', 'min:0'],
            'ar_tva' => ['required', 'numeric', 'min:0', 'max:100'],
            'ar_stock_min' => ['required', 'numeric', 'min:0'],
            'ar_stock_actuel' => ['required', 'numeric', 'min:0'],
            'ar_suivi_stock' => ['nullable', 'boolean'],
            'ar_unite' => ['required', 'string', 'max:50'],
        ]);

        $data['code_article'] = $data['code_article'] ?? $data['ar_ref'];
        $data['ar_prix_revient'] = array_key_exists('ar_prix_revient', $data) && $data['ar_prix_revient'] !== null
            ? $data['ar_prix_revient']
            : $data['ar_prix_achat'];
        $data['ar_suivi_stock'] = $request->boolean('ar_suivi_stock');

        return $data;
    }
}
