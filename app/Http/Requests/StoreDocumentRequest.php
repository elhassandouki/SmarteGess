<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDocumentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $types = ['DE', 'BC', 'BL', 'FA', 'BR', 'FR'];
        $statuts = ['en_attente', 'en_cours', 'livre'];

        return [
            'do_piece' => [
                'required',
                'string',
                'max:100',
                Rule::unique('f_docentete', 'do_piece'),
            ],
            'do_date' => ['required', 'date'],
            'tier_id' => ['nullable', 'exists:f_comptet,id'],
            'depot_id' => ['nullable', 'exists:f_depots,id'],
            'type_document_code' => ['required', Rule::in($types)],
            'transporteur_id' => ['nullable', 'exists:f_transporteurs,id'],
            'do_lieu_livraison' => ['nullable', 'string', 'max:255'],
            'do_date_livraison' => ['nullable', 'date'],
            'do_expedition_statut' => ['required', Rule::in($statuts)],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.article_id' => ['required', 'exists:f_articles,id'],
            'lines.*.dl_qte' => ['required', 'numeric', 'gt:0'],
            'lines.*.dl_prix_unitaire_ht' => ['required', 'numeric', 'min:0'],
            'lines.*.dl_remise_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'do_piece.required' => 'Le numero de document est requis.',
            'do_piece.unique' => 'Ce numero de document existe deja.',
            'type_document_code.required' => 'Le type de document est requis.',
            'lines.required' => 'Au moins une ligne de document est requise.',
            'lines.*.article_id.required' => 'Chaque ligne doit avoir un article.',
            'lines.*.dl_qte.required' => 'La quantite est requise.',
            'lines.*.dl_qte.gt' => 'La quantite doit etre superieure a 0.',
        ];
    }
}
