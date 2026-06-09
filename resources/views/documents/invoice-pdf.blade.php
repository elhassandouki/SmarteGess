<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facture {{ $document->do_piece }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Arial', sans-serif;
            color: #333;
            font-size: 11px;
            line-height: 1.4;
            background: #f5f5f5;
            padding: 20px;
        }

        .container {
            background: white;
            max-width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            padding: 40px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        .header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }

        .company-info h2 {
            font-size: 20px;
            margin-bottom: 5px;
        }

        .company-info p {
            font-size: 10px;
            margin: 2px 0;
        }

        .document-title {
            text-align: right;
        }

        .document-title h1 {
            font-size: 24px;
            color: #d32f2f;
            margin-bottom: 5px;
        }

        .document-title p {
            font-size: 12px;
            margin: 2px 0;
        }

        .content {
            margin: 30px 0;
        }

        .section {
            margin-bottom: 25px;
        }

        .section-title {
            background: #f0f0f0;
            padding: 8px 10px;
            font-weight: bold;
            font-size: 12px;
            margin-bottom: 10px;
            border-left: 4px solid #d32f2f;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .info-block {
            font-size: 10px;
        }

        .info-block label {
            font-weight: bold;
            color: #666;
            display: block;
            margin-bottom: 3px;
        }

        .info-block p {
            margin: 2px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        thead {
            background: #e0e0e0;
        }

        thead th {
            padding: 8px;
            text-align: left;
            font-weight: bold;
            font-size: 10px;
            border: 1px solid #ccc;
        }

        tbody td {
            padding: 8px;
            border: 1px solid #ddd;
            font-size: 10px;
        }

        tbody tr:nth-child(even) {
            background: #fafafa;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }

        .totals {
            margin-top: 20px;
            width: 60%;
            margin-left: auto;
            font-size: 11px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 10px;
            border-bottom: 1px solid #ddd;
        }

        .total-row.grand-total {
            background: #f0f0f0;
            font-weight: bold;
            font-size: 13px;
            border-top: 2px solid #333;
            border-bottom: 2px solid #333;
        }

        .total-row.payment-info {
            background: #fff3e0;
            font-weight: bold;
        }

        .total-row.remaining {
            background: #ffebee;
            font-weight: bold;
        }

        .notes {
            background: #f9f9f9;
            padding: 15px;
            border-left: 3px solid #d32f2f;
            margin: 20px 0;
            font-size: 10px;
        }

        .notes label {
            font-weight: bold;
            display: block;
            margin-bottom: 5px;
        }

        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 9px;
            color: #999;
        }

        .stamp-area {
            margin-top: 30px;
            padding: 30px;
            border: 2px dashed #ddd;
            text-align: center;
            color: #999;
            min-height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .page-break {
            page-break-after: always;
        }

        @media print {
            body { padding: 0; }
            .container { box-shadow: none; margin: 0; }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="company-info">
                @if($companySetting?->company_name)
                    <h2>{{ $companySetting->company_name }}</h2>
                @endif
                @if($companySetting?->company_registration)
                    <p><strong>RC:</strong> {{ $companySetting->company_registration }}</p>
                @endif
                @if($companySetting?->tax_id)
                    <p><strong>ID Fiscal:</strong> {{ $companySetting->tax_id }}</p>
                @endif
                @if($companySetting?->company_address)
                    <p>{{ $companySetting->company_address }}</p>
                @endif
                @if($companySetting?->company_phone)
                    <p>Tél: {{ $companySetting->company_phone }}</p>
                @endif
                @if($companySetting?->company_email)
                    <p>Email: {{ $companySetting->company_email }}</p>
                @endif
            </div>

            <div class="document-title">
                <h1>{{ $documentLabel }}</h1>
                <p><strong>N°:</strong> {{ $document->do_piece }}</p>
                <p><strong>Date:</strong> {{ optional($document->do_date)->format('d/m/Y') }}</p>
                @if($document->do_date_livraison)
                    <p><strong>Livraison:</strong> {{ $document->do_date_livraison->format('d/m/Y') }}</p>
                @endif
            </div>
        </div>

        <!-- Content -->
        <div class="content">
            <!-- Client/Supplier Info -->
            <div class="info-grid">
                <div class="info-block">
                    <label>CLIENT / FOURNISSEUR</label>
                    @if($document->tier)
                        <p>{{ $document->tier->ct_intitule ?? '-' }}</p>
                        @if($document->tier->ct_adresse)
                            <p>{{ $document->tier->ct_adresse }}</p>
                        @endif
                        @if($document->tier->ct_codepostal)
                            <p>{{ $document->tier->ct_codepostal }}
                                @if($document->tier->ct_ville) {{ $document->tier->ct_ville }} @endif
                            </p>
                        @endif
                        @if($document->tier->ct_telephone)
                            <p>Tél: {{ $document->tier->ct_telephone }}</p>
                        @endif
                    @else
                        <p>-</p>
                    @endif
                </div>

                <div class="info-block">
                    <label>INFORMATIONS DE LIVRAISON</label>
                    @if($document->do_lieu_livraison)
                        <p><strong>Lieu:</strong> {{ $document->do_lieu_livraison }}</p>
                    @endif
                    @if($document->depot)
                        <p><strong>Dépôt:</strong> {{ $document->depot->intitule }}</p>
                    @endif
                    @if($document->transporteur)
                        <p><strong>Transporteur:</strong> {{ $document->transporteur->name }}</p>
                    @endif
                    @if($document->do_expedition_statut)
                        <p><strong>Statut:</strong> {{ ucfirst(str_replace('_', ' ', $document->do_expedition_statut)) }}</p>
                    @endif
                </div>
            </div>

            <!-- Articles Table -->
            <div class="section">
                <div class="section-title">ARTICLES / SERVICES</div>
                <table>
                    <thead>
                        <tr>
                            <th style="width: 35%;">Désignation</th>
                            <th style="width: 10%;" class="text-center">Référence</th>
                            <th style="width: 10%;" class="text-right">Quantité</th>
                            <th style="width: 15%;" class="text-right">Prix Unitaire HT</th>
                            <th style="width: 10%;" class="text-right">Remise %</th>
                            <th style="width: 10%;" class="text-right">Taux TVA</th>
                            <th style="width: 10%;" class="text-right">Montant TTC</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($calculations['lineDetails'] as $detail)
                            <tr>
                                <td>{{ $detail['article']?->ar_design ?? '-' }}</td>
                                <td class="text-center">{{ $detail['article']?->code_article ?: $detail['article']?->ar_ref ?: '-' }}</td>
                                <td class="text-right">{{ number_format($detail['quantity'], 3, ',', ' ') }}</td>
                                <td class="text-right">{{ number_format($detail['unitPrice'], 2, ',', ' ') }} €</td>
                                <td class="text-right">
                                    @if($detail['discountPercent'] > 0)
                                        {{ number_format($detail['discountPercent'], 2, ',', ' ') }}%
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="text-right">{{ number_format($detail['taxRate'], 2, ',', ' ') }}%</td>
                                <td class="text-right"><strong>{{ number_format($detail['lineTTC'], 2, ',', ' ') }} €</strong></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Totals -->
            <div class="section">
                <div class="totals">
                    <div class="total-row">
                        <span>Sous-total HT:</span>
                        <span>{{ number_format($calculations['subtotal'], 2, ',', ' ') }} €</span>
                    </div>
                    <div class="total-row">
                        <span>Montant TVA:</span>
                        <span>{{ number_format($calculations['totalTax'], 2, ',', ' ') }} €</span>
                    </div>
                    <div class="total-row grand-total">
                        <span>TOTAL TTC:</span>
                        <span>{{ number_format($calculations['totalTTC'], 2, ',', ' ') }} €</span>
                    </div>

                    @if($calculations['paid'] > 0)
                        <div class="total-row payment-info">
                            <span>Montant Payé:</span>
                            <span>{{ number_format($calculations['paid'], 2, ',', ' ') }} €</span>
                        </div>
                    @endif

                    @if($calculations['remaining'] > 0)
                        <div class="total-row remaining">
                            <span>Restant Dû:</span>
                            <span>{{ number_format($calculations['remaining'], 2, ',', ' ') }} €</span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Conditions -->
            @if($companySetting?->payment_terms || $companySetting?->company_notes)
                <div class="notes">
                    @if($companySetting?->payment_terms)
                        <label>CONDITIONS DE PAIEMENT:</label>
                        <p>{{ $companySetting->payment_terms }}</p>
                    @endif
                    @if($companySetting?->company_notes)
                        <label>NOTES:</label>
                        <p>{{ $companySetting->company_notes }}</p>
                    @endif
                </div>
            @endif

            <!-- Signature Area -->
            <div class="stamp-area">
                Cachet et Signature du Fournisseur
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>Document généré le {{ now()->format('d/m/Y à H:i') }}</p>
            <p>Cet document est une facture détaillée et engage légalement les deux parties.</p>
        </div>
    </div>
</body>
</html>
