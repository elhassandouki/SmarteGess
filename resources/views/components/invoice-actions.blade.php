<!-- Invoice and Thermal Ticket Actions -->
<div class="invoice-actions mb-3">
    <div class="btn-group" role="group">
        <button type="button" class="btn btn-primary btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <i class="fas fa-file-pdf"></i> Facture
        </button>
        <div class="dropdown-menu">
            <a class="dropdown-item" href="{{ route('invoices.preview', $document) }}" target="_blank">
                <i class="fas fa-eye"></i> Aperçu Facture
            </a>
            <a class="dropdown-item" href="{{ route('invoices.pdf', $document) }}">
                <i class="fas fa-download"></i> Télécharger PDF
            </a>
        </div>
    </div>

    <div class="btn-group" role="group">
        <button type="button" class="btn btn-info btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <i class="fas fa-print"></i> Ticket Thermique
        </button>
        <div class="dropdown-menu">
            <a class="dropdown-item" href="{{ route('invoices.thermal-preview', $document) }}" target="_blank">
                <i class="fas fa-eye"></i> Aperçu Ticket
            </a>
            <a class="dropdown-item" href="{{ route('invoices.thermal', $document) }}">
                <i class="fas fa-print"></i> Imprimer Ticket
            </a>
            <div class="dropdown-divider"></div>
            <a class="dropdown-item" href="{{ route('invoices.thermal-escpos', $document) }}">
                <i class="fas fa-download"></i> ESC/POS (Imprimante Directe)
            </a>
        </div>
    </div>
</div>

<style>
    .invoice-actions .btn-group {
        margin-right: 0.5rem;
    }
</style>
