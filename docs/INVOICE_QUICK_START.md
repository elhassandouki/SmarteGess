# Quick Integration Guide: Invoice PDF & Thermal Tickets

## 📋 What You Can Do Now

### For Every Document Type (Devis, BC, BL, Facture, etc.)
1. ✅ Generate detailed invoice PDFs
2. ✅ Create thermal printer tickets
3. ✅ Export for direct printer integration

---

## 🚀 Quick Start

### 1. **Add Action Buttons to Document View**

In your document show view (`resources/views/documents/show.blade.php`):

```blade
<!-- Add this line where you want the buttons to appear -->
<x-invoice-actions :document="$document" />
```

Or manually add buttons:

```blade
<div class="btn-group">
    <a href="{{ route('invoices.preview', $document) }}" class="btn btn-primary">
        📄 Voir Facture
    </a>
    <a href="{{ route('invoices.pdf', $document) }}" class="btn btn-primary">
        💾 Télécharger PDF
    </a>
    <a href="{{ route('invoices.thermal-preview', $document) }}" class="btn btn-info">
        🖨️ Ticket Thermique
    </a>
</div>
```

### 2. **Access Routes Programmatically**

```php
// In your controller or view
$invoicePdfUrl = route('invoices.pdf', $document);
$ticketUrl = route('invoices.thermal', $document);
$previewUrl = route('invoices.preview', $document);
```

### 3. **JavaScript Integration**

```javascript
// Download invoice
function downloadInvoice(documentId) {
    window.location.href = `/invoices/${documentId}/pdf`;
}

// Open thermal preview
function printTicket(documentId) {
    window.open(`/invoices/${documentId}/thermal-preview`, 'printer');
}

// Send to thermal printer (ESC/POS)
async function printToThermal(documentId) {
    const response = await fetch(`/invoices/${documentId}/thermal-escpos`);
    const buffer = await response.arrayBuffer();
    // Send to printer (depends on your printer API)
}
```

---

## 📊 API Endpoints

All endpoints require user authentication and `documents.view` permission.

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/invoices/{id}/pdf` | GET | Download invoice PDF |
| `/invoices/{id}/preview` | GET | Preview invoice in browser |
| `/invoices/{id}/thermal` | GET | Thermal ticket HTML |
| `/invoices/{id}/thermal-preview` | GET | Preview thermal ticket |
| `/invoices/{id}/thermal-escpos` | GET | Raw ESC/POS data |

---

## 🎨 Customization

### Change Invoice Colors/Styling
Edit: `resources/views/documents/invoice-pdf.blade.php`

### Change Thermal Ticket Format
Edit: `resources/views/documents/thermal-ticket.blade.php`

### Change Configuration
Edit: `config/invoice.php`

---

## 🖨️ Thermal Printer Setup

### Option 1: HTML Printing (Easiest)
1. Click "Ticket Thermique" link
2. Use browser print (Ctrl+P)
3. Select your thermal printer
4. Print!

### Option 2: Direct Integration
Send ESC/POS data to printer:
```javascript
const esposUrl = `/invoices/123/thermal-escpos`;
// Implement your printer driver API
```

---

## ✅ What's Included

### Services
- ✅ `InvoicePdfService` - Invoice PDF generation
- ✅ `ThermalPrinterTicketService` - Ticket generation

### Controllers
- ✅ `InvoiceController` - All endpoints

### Templates
- ✅ Professional invoice PDF template (A4)
- ✅ Thermal ticket template (80mm)
- ✅ Action buttons component

### Configuration
- ✅ `config/invoice.php` - All settings

### Documentation
- ✅ Full guide in `docs/INVOICE_THERMAL_GUIDE.md`

---

## 📝 Next Steps

1. **Test It**
   - Go to any document
   - Click the invoice/thermal buttons
   - Verify PDF and ticket generation

2. **Customize**
   - Edit templates to match your branding
   - Update company details in settings
   - Configure tax rates for articles

3. **Integrate**
   - Add buttons to your document views
   - Connect thermal printer (if using hardware)
   - Set up automated invoice generation (optional)

---

## 🔐 Authorization

All routes require:
- ✅ User authentication (`middleware('auth')`)
- ✅ Document view permission (`can:documents.view`)
- ✅ Document ownership (via policy)

---

## 📞 Support

For detailed information, see: `docs/INVOICE_THERMAL_GUIDE.md`

For troubleshooting:
1. Check browser console for errors
2. Verify document has proper data
3. Check that articles have tax associations
4. Ensure company settings are configured

---

## 🎯 Features at a Glance

| Feature | Invoice PDF | Thermal Ticket |
|---------|------------|----------------|
| All document types | ✅ | ✅ |
| Tax calculation | ✅ | ✅ |
| Payment tracking | ✅ | ✅ |
| Discounts | ✅ | ✅ |
| Delivery info | ✅ | ✅ |
| Company branding | ✅ | ✅ |
| Professional layout | ✅ | ✅ |
| 80mm thermal format | — | ✅ |
| ESC/POS export | — | ✅ |
| HTML preview | ✅ | ✅ |
| Browser print | ✅ | ✅ |

---

**Version**: 1.0
**Status**: Ready for production
**Last Updated**: 2026-06-09
