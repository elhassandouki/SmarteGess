<?php

namespace App\Services\Export;

use App\Models\Document;
use App\Services\PdfRendererService;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentExportService
{
    public function __construct(
        private readonly PdfRendererService $pdfRendererService
    ) {
    }

    public function exportDocumentPdf(Document $document)
    {
        $document->load(['tier', 'depot', 'transporteur', 'lines.article']);

        return $this->pdfRendererService->download(
            'documents.export-pdf',
            [
                'document' => $document,
                'aiSummary' => $this->documentSummary($document),
            ],
            'document-'.$document->id.'.pdf'
        );
    }

    public function exportDocumentExcel(Document $document): StreamedResponse
    {
        $document->load(['tier', 'depot', 'transporteur', 'lines.article']);
        $filename = 'document-'.$document->id.'.csv';

        return response()->streamDownload(function () use ($document): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Piece', $document->do_piece]);
            fputcsv($handle, ['Date', optional($document->do_date)->toDateString()]);
            fputcsv($handle, ['Type', $document->type_document_code]);
            fputcsv($handle, ['Client/Fournisseur', $document->tier?->ct_intitule ?? '']);
            fputcsv($handle, ['Depot', $document->depot?->intitule ?? '']);
            fputcsv($handle, []);
            fputcsv($handle, ['Article', 'Reference', 'Quantite', 'PU HT', 'Montant HT']);

            foreach ($document->lines as $line) {
                fputcsv($handle, [
                    $line->article?->ar_design ?? '',
                    $line->article?->code_article ?: $line->article?->ar_ref,
                    (float) $line->dl_qte,
                    (float) $line->dl_prix_unitaire_ht,
                    (float) $line->dl_montant_ht,
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function documentSummary(Document $document): string
    {
        $lineCount = $document->lines->count();
        return sprintf(
            'Document %s du %s avec %d lignes. Total TTC: %.2f.',
            $document->do_piece,
            optional($document->do_date)->format('d/m/Y'),
            $lineCount,
            (float) $document->do_total_ttc
        );
    }
}

