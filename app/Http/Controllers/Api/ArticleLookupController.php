<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ArticleLookupController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));

        if ($q === '') {
            return response()->json(['data' => []]);
        }

        $rows = Article::query()
            ->where(function ($query) use ($q) {
                $query->where('ar_code_barre', 'like', '%'.$q.'%')
                    ->orWhere('code_article', 'like', '%'.$q.'%')
                    ->orWhere('ar_ref', 'like', '%'.$q.'%')
                    ->orWhere('ar_design', 'like', '%'.$q.'%');
            })
            ->orderBy('ar_design')
            ->limit(15)
            ->get();

        return response()->json(['data' => $rows->map(fn (Article $a) => $this->toPayload($a))->values()]);
    }

    public function barcode(string $code): JsonResponse
    {
        $article = Article::query()
            ->where('ar_code_barre', $code)
            ->orWhere('code_article', $code)
            ->orWhere('ar_ref', $code)
            ->first();

        if (!$article) {
            return response()->json(['message' => 'Article not found'], 404);
        }

        return response()->json(['data' => $this->toPayload($article)]);
    }

    private function toPayload(Article $article): array
    {
        return [
            'id' => $article->id,
            'barcode' => (string) ($article->ar_code_barre ?? ''),
            'code' => (string) ($article->code_article ?: $article->ar_ref),
            'name' => (string) $article->ar_design,
            'label' => ($article->code_article ?: $article->ar_ref).' - '.$article->ar_design,
            'price' => (float) $article->ar_prix_vente,
            'buy_price' => (float) $article->ar_prix_achat,
            'vat' => (float) $article->ar_tva,
            'stock' => (float) $article->ar_stock_actuel,
        ];
    }
}
