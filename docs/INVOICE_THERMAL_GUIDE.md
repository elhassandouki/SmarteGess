# Documentation: Invoice PDF & Thermal Printer Tickets

## Overview

The system now provides comprehensive invoice PDF generation and thermal printer ticket generation for **ALL document types** (Devis, BC, BL, Facture, BA, FF, BR, FR, etc.).

This feature allows you to:
- Generate detailed invoice PDFs with professional formatting
- Create thermal printer tickets for point-of-sale operations
- Support multiple document types with automatic format adaptation
- Export tickets in ESC/POS format for direct thermal printer integration

---

## Features

### 1. **Detailed Invoice PDF**
- Professional, printable format (A4)
- Complete company information
- Customer/supplier details
- Itemized line items with quantities, prices, and discounts
- Tax calculations and breakdown
- Payment status and remaining amount due
- Customizable footer with signature area
- Automatic generation for all document types

### 2. **Thermal Printer Tickets**
- 80mm thermal printer format
- Quick-printable receipt-style tickets
- Item list with quantities and prices
- Tax and total summary
- Payment information
- Optional company branding
- Support for automatic paper cutting

### 3. **ESC/POS Format Export**
- Raw ESC/POS commands for direct printer integration
- Compatible with standard thermal printers
- Automatic paper cutting commands
- Perfect for automated point-of-sale systems

---

## API Routes

### Invoice PDF Routes

#### Download Invoice PDF
```
GET /invoices/{document}/pdf
```
Downloads a detailed invoice PDF for the document.

**Example:**
```
https://app.com/invoices/123/pdf
```

#### Preview Invoice PDF
```
GET /invoices/{document}/preview
```
Opens the invoice PDF in browser for preview.

**Example:**
```
https://app.com/invoices/123/preview
```

### Thermal Ticket Routes

#### Print Thermal Ticket
```
GET /invoices/{document}/thermal
```
Returns HTML thermal ticket format for printing.

**Example:**
```
https://app.com/invoices/123/thermal
```

#### Preview Thermal Ticket
```
GET /invoices/{document}/thermal-preview
```
Opens the thermal ticket in browser for preview.

**Example:**
```
https://app.com/invoices/123/thermal-preview
```

#### Get ESC/POS Format
```
GET /invoices/{document}/thermal-escpos
```
Returns raw ESC/POS binary data for direct thermal printer integration.

**Example:**
```
https://app.com/invoices/123/thermal-escpos
```

---

## Usage in Views

### Adding Invoice/Ticket Buttons to Document View

Include the invoice-actions component in your document view:

```blade
@include('components.invoice-actions', ['document' => $document])
```

Or use it as a Blade component:

```blade
<x-invoice-actions :document="$document" />
```

This will display dropdown buttons with all available invoice and ticket actions.

### Manual Link Creation

Create individual links in your view:

```blade
<!-- Invoice PDF Download -->
<a href="{{ route('invoices.pdf', $document) }}" class="btn btn-primary">
    <i class="fas fa-file-pdf"></i> Télécharger Facture
</a>

<!-- Preview Invoice -->
<a href="{{ route('invoices.preview', $document) }}" target="_blank" class="btn btn-info">
    <i class="fas fa-eye"></i> Aperçu Facture
</a>

<!-- Thermal Ticket -->
<a href="{{ route('invoices.thermal-preview', $document) }}" target="_blank" class="btn btn-warning">
    <i class="fas fa-print"></i> Ticket Thermique
</a>
```

---

## Supported Document Types

The system supports invoices and tickets for all document types:

| Code | Label | Module |
|------|-------|--------|
| DE | Devis Client | Sales |
| BC | Commande Client | Sales |
| BL | Bon de Livraison | Sales |
| FA | Facture Client | Sales |
| BA | Commande Fournisseur | Purchase |
| FF | Facture Fournisseur | Purchase |
| BR | Bon de Retour Client | Sales |
| FR | Facture Retour Client | Sales |

---

## Configuration

Configuration is managed in `config/invoice.php`:

```php
return [
    'invoice' => [
        'enabled' => true,
        'paper_format' => 'A4',
        'orientation' => 'portrait',
        'show_company_details' => true,
        'show_customer_details' => true,
        'show_payment_info' => true,
        'show_delivery_info' => true,
        'include_line_details' => true,
        'show_tax_breakdown' => true,
        'footer_message' => 'Merci de votre confiance',
        'show_signature_area' => true,
    ],
    'thermal' => [
        'enabled' => true,
        'paper_width_mm' => 80,
        'char_width' => 12,
        'show_company_name' => true,
        'show_customer_details' => true,
        'show_payment_status' => true,
        'include_item_details' => true,
        'show_tax_info' => true,
        'footer_message' => 'Merci de votre visite',
        'auto_cut' => true,
        'full_cut' => false,
    ],
];
```

---

## Services

### InvoicePdfService

Handles all invoice PDF generation logic.

**Methods:**
- `generateInvoicePdf(Document $document)` - Generate and download invoice PDF
- `calculateTotals(Document $document)` - Calculate detailed totals and tax info

**Location:** `app/Services/Export/InvoicePdfService.php`

### ThermalPrinterTicketService

Handles thermal printer ticket generation.

**Methods:**
- `generateTicketEscPos(Document $document)` - Generate ESC/POS format
- `generateTicketHtml(Document $document)` - Generate HTML format
- `calculateTotals(Document $document)` - Calculate totals for ticket

**Location:** `app/Services/Export/ThermalPrinterTicketService.php`

### InvoiceController

Web controller handling all invoice and ticket routes.

**Methods:**
- `downloadInvoicePdf(Document $document)` - Download invoice PDF
- `previewInvoicePdf(Document $document)` - Preview invoice in browser
- `printThermalTicket(Document $document)` - Display thermal ticket for printing
- `previewThermalTicket(Document $document)` - Preview thermal ticket
- `getThermalTicketEscPos(Document $document)` - Get raw ESC/POS data

**Location:** `app/Http/Controllers/InvoiceController.php`

---

## Tax Calculation

Taxes are calculated on each line item:

```
Line Total = Quantity × Unit Price
Discount = Line Total × (Discount % / 100)
Line HT = Line Total - Discount
Line Tax = Line HT × (Tax Rate / 100)
Line TTC = Line HT + Line Tax
```

The tax rate is retrieved from the article's associated tax.

---

## Thermal Printer Integration

### HTML Format (Recommended for Browsers)
The HTML format is optimized for 80mm thermal printers and can be printed directly from the browser:

1. Navigate to `/invoices/{document}/thermal-preview`
2. Use browser's print function (Ctrl+P)
3. Ensure margins are set to minimal/none
4. Print to your thermal printer

### ESC/POS Format (For Programmable Integration)
The ESC/POS format returns raw binary data:

```
GET /invoices/{document}/thermal-escpos
```

This can be sent directly to a thermal printer via:
- Network socket connection
- USB connection
- Serial port connection

**Example JavaScript (Web Socket):**
```javascript
fetch('/invoices/123/thermal-escpos')
    .then(response => response.arrayBuffer())
    .then(buffer => {
        // Send to thermal printer via USB/Network
        // Implementation depends on printer API
    });
```

---

## Customization

### Modifying Invoice Template
Edit `resources/views/documents/invoice-pdf.blade.php` to customize the invoice appearance.

### Modifying Thermal Ticket Template
Edit `resources/views/documents/thermal-ticket.blade.php` to customize the ticket format.

### Adding Custom Fields
Extend the services to include custom fields in calculations:

```php
// In InvoicePdfService or ThermalPrinterTicketService
private function calculateCustomFields(Document $document): array
{
    // Add your custom calculations here
}
```

---

## Authorization

All invoice and ticket routes are protected with the `documents.view` permission. Users must have access to view documents to download/preview invoices and tickets.

---

## Troubleshooting

### PDF Not Generating
- Ensure `barryvdh/laravel-dompdf` is installed
- Check that PDF renderer service is properly configured
- Verify CompanySetting has required data

### Thermal Ticket Formatting Issues
- Verify character width setting in config matches your printer (typically 12 for 80mm)
- Test with HTML preview first before attempting ESC/POS
- Ensure printer supports the standard ESC/POS commands

### Missing Tax Information
- Verify articles have tax associations
- Check that tax rates are properly configured
- Validate line items have correct article references

---

## File Locations

```
├── app/
│   ├── Services/Export/
│   │   ├── InvoicePdfService.php
│   │   └── ThermalPrinterTicketService.php
│   └── Http/Controllers/
│       └── InvoiceController.php
├── resources/views/documents/
│   ├── invoice-pdf.blade.php
│   └── thermal-ticket.blade.php
├── resources/views/components/
│   └── invoice-actions.blade.php
├── config/
│   └── invoice.php
└── routes/
    └── web.php
```

---

## Support

For issues or feature requests, please contact support with details about:
- Document type affected
- Expected vs. actual output
- Steps to reproduce the issue
