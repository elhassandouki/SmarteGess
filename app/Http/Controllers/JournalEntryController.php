<?php

namespace App\Http\Controllers;

use App\Models\JournalEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JournalEntryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): View|JsonResponse
    {
        $search = trim((string) $request->string('search'));
        $status = $request->string('status', '');
        $fromDate = $request->date('from_date');
        $toDate = $request->date('to_date');

        $query = JournalEntry::query()
            ->with('lines')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('journal_code', 'like', '%'.$search.'%')
                        ->orWhere('reference_number', 'like', '%'.$search.'%')
                        ->orWhere('label', 'like', '%'.$search.'%');
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($fromDate, fn ($query) => $query->whereDate('entry_date', '>=', $fromDate))
            ->when($toDate, fn ($query) => $query->whereDate('entry_date', '<=', $toDate));

        if ($request->ajax() && $request->has('draw')) {
            $draw = (int) $request->input('draw', 1);
            $start = max(0, (int) $request->input('start', 0));
            $length = max(10, (int) $request->input('length', 10));
            $recordsTotal = (clone $query)->count();
            $recordsFiltered = $recordsTotal;
            $rows = $query->orderByDesc('entry_date')->skip($start)->take($length)->get();

            $statusBadges = [
                'draft' => 'info',
                'posted' => 'success',
                'reversed' => 'danger',
            ];

            $data = $rows->map(function (JournalEntry $entry) use ($statusBadges) {
                $debitTotal = $entry->lines->sum('debit');
                $creditTotal = $entry->lines->sum('credit');
                $color = $statusBadges[$entry->status] ?? 'secondary';

                return [
                    'entry_date' => $entry->entry_date?->format('d/m/Y'),
                    'journal_code' => e($entry->journal_code),
                    'reference_number' => e($entry->reference_number ?? '-'),
                    'label' => e($entry->label),
                    'debit' => number_format($debitTotal, 2, ',', ' '),
                    'credit' => number_format($creditTotal, 2, ',', ' '),
                    'status' => '<span class="badge badge-'.$color.'">'.ucfirst($entry->status).'</span>',
                    'actions' => '<div class="btn-group btn-group-sm" role="group">'
                        .'<a href="'.route('accounting.entries.show', $entry).'" class="btn btn-xs btn-outline-secondary mr-2"><i class="fas fa-eye"></i></a>'
                        .'</div>',
                ];
            })->all();

            return response()->json(compact('draw', 'recordsTotal', 'recordsFiltered', 'data'));
        }

        $entries = $query->orderByDesc('entry_date')->get();

        return view('accounting.entries.index', [
            'entries' => $entries,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'from_date' => $fromDate?->format('Y-m-d'),
                'to_date' => $toDate?->format('Y-m-d'),
            ],
            'statuses' => [
                'draft' => 'Brouillon',
                'posted' => 'Validee',
                'reversed' => 'Annulee',
            ],
        ]);
    }

    public function show(JournalEntry $entry): View
    {
        $entry->load('lines');

        return view('accounting.entries.show', compact('entry'));
    }
}
