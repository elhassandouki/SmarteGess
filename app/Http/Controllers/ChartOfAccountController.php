<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChartOfAccountController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): View|JsonResponse
    {
        $search = trim((string) $request->string('search'));
        $type = $request->string('type', '');
        $activeOnly = $request->boolean('active_only', true);

        $query = ChartOfAccount::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('account_code', 'like', '%'.$search.'%')
                        ->orWhere('account_label', 'like', '%'.$search.'%');
                });
            })
            ->when($type !== '', fn ($query) => $query->where('account_type', $type))
            ->when($activeOnly, fn ($query) => $query->where('is_active', true));

        if ($request->ajax() && $request->has('draw')) {
            $draw = (int) $request->input('draw', 1);
            $start = max(0, (int) $request->input('start', 0));
            $length = max(10, (int) $request->input('length', 10));
            $recordsTotal = (clone $query)->count();
            $recordsFiltered = $recordsTotal;
            $rows = $query->orderBy('account_code')->skip($start)->take($length)->get();

            $typeMap = [
                'asset' => 'Actif',
                'liability' => 'Passif',
                'equity' => 'Capitaux propres',
                'revenue' => 'Produit',
                'expense' => 'Charge',
            ];

            $data = $rows->map(function (ChartOfAccount $account) use ($typeMap) {
                return [
                    'account_code' => e($account->account_code),
                    'account_label' => e($account->account_label),
                    'account_type' => $typeMap[$account->account_type] ?? $account->account_type,
                    'is_active' => $account->is_active ? '<span class="badge badge-success">Actif</span>' : '<span class="badge badge-secondary">Inactif</span>',
                    'actions' => '<div class="btn-group btn-group-sm" role="group">'
                        .'<a href="'.route('accounting.accounts.show', $account).'" class="btn btn-xs btn-outline-secondary mr-2"><i class="fas fa-eye"></i></a>'
                        .'</div>',
                ];
            })->all();

            return response()->json(compact('draw', 'recordsTotal', 'recordsFiltered', 'data'));
        }

        $accounts = $query->orderBy('account_code')->get();

        return view('accounting.accounts.index', [
            'accounts' => $accounts,
            'filters' => [
                'search' => $search,
                'type' => $type,
                'active_only' => $activeOnly,
            ],
            'types' => [
                'asset' => 'Actif',
                'liability' => 'Passif',
                'equity' => 'Capitaux propres',
                'revenue' => 'Produit',
                'expense' => 'Charge',
            ],
        ]);
    }

    public function show(ChartOfAccount $account): View
    {
        $account->load('journalEntryLines');

        return view('accounting.accounts.show', compact('account'));
    }
}
