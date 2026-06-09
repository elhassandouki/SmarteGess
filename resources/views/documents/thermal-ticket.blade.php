<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Thermique {{ $document->do_piece }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            width: 80mm;
            margin: 0 auto;
            padding: 0;
            background: white;
            line-height: 1.3;
        }

        .ticket {
            width: 80mm;
            padding: 10px;
            background: white;
        }

        .center {
            text-align: center;
            margin: 5px 0;
        }

        .bold {
            font-weight: bold;
        }

        .separator {
            border-top: 1px dashed #000;
            margin: 8px 0;
            padding: 0;
        }

        .header {
            text-align: center;
            margin-bottom: 10px;
        }

        .header .company {
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 5px;
        }

        .header .doc-type {
            font-weight: bold;
            font-size: 13px;
            text-decoration: underline;
            margin: 5px 0;
        }

        .header .doc-number {
            font-size: 11px;
            margin: 2px 0;
        }

        .customer {
            font-size: 11px;
            margin: 8px 0;
            text-align: center;
        }

        .customer-name {
            font-weight: bold;
            margin: 2px 0;
        }

        .items {
            font-size: 11px;
            margin: 8px 0;
        }

        .item-header {
            display: grid;
            grid-template-columns: 1fr 0.5fr 0.7fr;
            gap: 2px;
            font-weight: bold;
            margin-bottom: 4px;
            text-align: left;
        }

        .item {
            margin: 5px 0;
            text-align: left;
        }

        .item-name {
            font-size: 11px;
            font-weight: bold;
            word-wrap: break-word;
            word-break: break-word;
        }

        .item-detail {
            font-size: 10px;
            color: #555;
            margin-top: 2px;
        }

        .item-row {
            display: flex;
            justify-content: space-between;
            font-size: 10px;
        }

        .total-section {
            margin: 8px 0;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            margin: 3px 0;
            font-size: 11px;
        }

        .total-row.subtotal {
            padding-bottom: 3px;
        }

        .total-row.final {
            font-weight: bold;
            font-size: 12px;
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
            padding: 3px 0;
            margin: 5px 0;
        }

        .payment-section {
            margin: 8px 0;
            text-align: center;
            font-size: 10px;
        }

        .payment-row {
            display: flex;
            justify-content: space-between;
            margin: 2px 0;
        }

        .footer {
            text-align: center;
            margin-top: 10px;
            font-size: 10px;
        }

        .footer-text {
            margin: 3px 0;
        }

        .cut-line {
            text-align: center;
            margin: 8px 0;
            color: #999;
            font-size: 9px;
        }

        @media print {
            body {
                margin: 0;
                padding: 0;
                width: 80mm;
            }
            .ticket {
                page-break-after: always;
            }
        }
    </style>
</head>
<body>
    <div class="ticket">
        <!-- Header -->
        <div class="header">
            @if($companySetting?->company_name)
                <div class="company">{{ strtoupper($companySetting->company_name) }}</div>
            @endif
            <div class="doc-type">{{ strtoupper($documentLabel) }}</div>
            <div class="doc-number">N°: <span class="bold">{{ $document->do_piece }}</span></div>
            <div class="doc-number">{{ optional($document->do_date)->format('d/m/Y H:i') }}</div>
        </div>

        <div class="separator"></div>

        <!-- Customer -->
        @if($document->tier)
            <div class="customer">
                <div class="customer-name">{{ strtoupper($document->tier->ct_intitule ?? '-') }}</div>
                @if($document->tier->ct_adresse)
                    <div style="font-size: 10px;">{{ substr($document->tier->ct_adresse, 0, 40) }}</div>
                @endif
            </div>
            <div class="separator"></div>
        @endif

        <!-- Items -->
        <div class="items">
            <div class="item-header">
                <span>Article</span>
                <span>Qté</span>
                <span>Prix</span>
            </div>

            @foreach($document->lines as $line)
                <div class="item">
                    <div class="item-name">
                        {{ substr($line->article?->ar_design ?? $line->article?->code_article ?? '-', 0, 30) }}
                    </div>
                    <div class="item-detail">
                        Réf: {{ $line->article?->code_article ?: $line->article?->ar_ref ?: '-' }}
                    </div>
                    <div class="item-row">
                        <span>{{ number_format((float)$line->dl_qte, 2, ',', ' ') }}</span>
                        <span>x</span>
                        <span>{{ number_format((float)$line->dl_prix_unitaire_ht, 2, ',', ' ') }} €</span>
                    </div>
                    @if((float)($line->dl_remise_percent ?? 0) > 0)
                        <div class="item-row" style="color: #d32f2f;">
                            <span>Remise:</span>
                            <span>-{{ number_format((float)$line->dl_remise_percent, 1, ',', ' ') }}%</span>
                        </div>
                    @endif
                    <div class="item-row" style="font-weight: bold; border-top: 1px dotted #999; padding-top: 2px;">
                        <span>Montant:</span>
                        <span>{{ number_format((float)$line->dl_montant_ht * (1 - ((float)($line->dl_remise_percent ?? 0) / 100)) * (1 + ((float)($line->article?->tax?->tax_rate ?? 0) / 100)), 2, ',', ' ') }} €</span>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="separator"></div>

        <!-- Totals -->
        <div class="total-section">
            <div class="total-row subtotal">
                <span>Sous-total HT:</span>
                <span>{{ number_format($totals['subtotal'], 2, ',', ' ') }} €</span>
            </div>

            @if($totals['tax'] > 0)
                <div class="total-row subtotal">
                    <span>TVA:</span>
                    <span>{{ number_format($totals['tax'], 2, ',', ' ') }} €</span>
                </div>
            @endif

            <div class="total-row final">
                <span>TOTAL TTC</span>
                <span>{{ number_format($totals['total'], 2, ',', ' ') }} €</span>
            </div>
        </div>

        <!-- Payment Info -->
        @if($totals['paid'] > 0 || $totals['remaining'] > 0)
            <div class="payment-section">
                @if($totals['paid'] > 0)
                    <div class="payment-row">
                        <span>Payé:</span>
                        <span>{{ number_format($totals['paid'], 2, ',', ' ') }} €</span>
                    </div>
                @endif

                @if($totals['remaining'] > 0)
                    <div class="payment-row" style="font-weight: bold; color: #d32f2f;">
                        <span>Restant dû:</span>
                        <span>{{ number_format($totals['remaining'], 2, ',', ' ') }} €</span>
                    </div>
                @else
                    <div class="payment-row" style="font-weight: bold; color: #4caf50;">
                        <span>Statut:</span>
                        <span>PAYÉ</span>
                    </div>
                @endif
            </div>
        @endif

        <div class="separator"></div>

        <!-- Footer -->
        <div class="footer">
            <div class="footer-text">Merci de votre achat</div>
            <div class="footer-text">{{ date('d/m/Y H:i:s') }}</div>
            <div class="cut-line">✂ - - - - - - - - - -</div>
        </div>
    </div>
</body>
</html>
