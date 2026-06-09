<!-- Invoice and Thermal Ticket Actions -->
<div class="invoice-actions btn-group mb-3" role="group">
    <div class="btn-group" role="group">
        <button type="button" class="btn btn-primary btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fas fa-file-pdf"></i> Facture
        </button>
        <ul class="dropdown-menu">
            <li>
                <a class="dropdown-item" href="{{ route('invoices.preview', $document) }}" target="_blank">
                    <i class="fas fa-eye"></i> Aperçu Facture
                </a>
            </li>
            <li>
                <a class="dropdown-item" href="{{ route('invoices.pdf', $document) }}">
                    <i class="fas fa-download"></i> Télécharger PDF
                </a>
            </li>
        </ul>
    </div>

    <div class="btn-group" role="group">
        <button type="button" class="btn btn-info btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fas fa-print"></i> Ticket Thermique
        </button>
        <ul class="dropdown-menu">
            <li>
                <a class="dropdown-item" href="{{ route('invoices.thermal-preview', $document) }}" target="_blank">
                    <i class="fas fa-eye"></i> Aperçu Ticket
                </a>
            </li>
            <li>
                <a class="dropdown-item" href="{{ route('invoices.thermal', $document) }}">
                    <i class="fas fa-print"></i> Imprimer Ticket
                </a>
            </li>
            <li>
                <a class="dropdown-item" href="{{ route('invoices.thermal-escpos', $document) }}">
                    <i class="fas fa-download"></i> ESC/POS (Imprimante Thermique)
                </a>
            </li>
        </ul>
    </div>
</div>

<style>
    .invoice-actions {
        display: flex;
        gap: 0.5rem;
    }

    .invoice-actions .btn-group {
        flex: 0 0 auto;
    }

    .invoice-actions .dropdown-menu {
        min-width: 220px;
    }
</style>
