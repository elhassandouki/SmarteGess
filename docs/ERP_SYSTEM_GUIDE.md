# SmartGess ERP System - Complete Implementation Guide

## Overview

SmartGess is a comprehensive commercial management ERP system built with Laravel. It provides complete workflow management for sales, purchases, inventory, payments, and business documents.

## Key Features Implemented

### 1. Document Management System

- **6 Document Types:**
    - **DE (Devis)** - Quote/Estimate (no stock effect)
    - **BC (Bon de Commande)** - Purchase/Sales Order
    - **BL (Bon de Livraison)** - Delivery/Shipment Note (reduces stock)
    - **FA (Facture)** - Invoice (reduces stock)
    - **BR (Bon de Retour)** - Return Note (reduces stock)
    - **FR (Facture Retour)** - Return Invoice (reduces stock)

### 2. Automatic Stock Management

- **Stock Adjustments:** Automatically triggered when documents are created, updated, or deleted
- **Movement Types:**
    - **IN:** Purchases (increases stock)
    - **OUT:** Sales/Shipments (decreases stock)
    - **ADJUSTMENT:** Manual stock corrections
    - **RETURN:** Return transactions
- **Multi-Depot Support:** Track stock per article per warehouse/depot
- **Audit Trail:** Complete history of all stock movements with timestamps and user tracking

### 3. Payment Workflow

- **Payment Status Tracking:**
    - **0 = Unpaid** - No payments received
    - **1 = Partially Paid** - Some payments received
    - **2 = Paid** - Full amount received
- **Payment Modes:** Cash (Especes), Check (Cheque), Bank Transfer (Virement), Draft (Effet/Traite)
- **Automatic Status Updates:** Payments are tracked per document with `do_montant_regle` field
- **Payment History:** View all payments linked to each document

### 4. Business Objects

#### Articles (Products)

- Article codes and descriptions
- Pricing: Purchase, Sale, Cost prices
- VAT/Tax percentage
- Stock tracking (current level, minimum level)
- Unit of measurement
- Barcode support

#### Families (Product Categories)

- Organize articles by category
- Hierarchical classification

#### Tiers (Customers/Suppliers)

- Complete customer/supplier information
- Payment terms and credit limits
- Tax identification (ICE, SIRET)
- Contact details
- Classification (customer, supplier, prospect)

#### Depots (Warehouses)

- Multiple warehouse management
- Stock is tracked per article per depot
- Area/location management

#### Transporters

- Carrier information
- Driver details
- Contact information

## Complete Workflows

### Sales Workflow

```
1. Create BC (Purchase Order/Sales Order)
   - Select customer
   - Choose depot
   - Add line items with quantities and prices
   → Stock is NOT affected yet

2. Create BL (Delivery Note)
   - Often based on BC
   - Specifies what's being shipped
   → Stock DECREASES automatically

3. Create FA (Invoice)
   - Final billing document
   - Can have different quantities/pricing
   → Stock DECREASES (if different from BL)

4. Add Reglement (Payment)
   - Record payment received
   - Select payment mode
   - Mark as validated when confirmed
   → Document status updates automatically
   → When fully paid, `do_statut` = 2
```

### Purchase Workflow

```
1. Create BC (Purchase Order - as inbound)
   - Select supplier
   - Choose receiving depot
   - Add line items with quantities
   → Stock INCREASES automatically when BC is confirmed

2. Create Invoice/Receipt
   - Match PO to received goods
   - Can adjust quantities if needed

3. Add Reglement (Payment to Supplier)
   - Record payment made
   - Track payment status
```

### Stock Movement Tracking

Each stock movement is recorded with:

- Article reference
- Depot location
- Movement type (IN/OUT/ADJUSTMENT/RETURN)
- Quantity moved
- Document reference (type and number)
- User who made the change
- Timestamp
- Optional notes

## Technical Architecture

### Models

- **Document** - Main document header
- **DocumentLine** - Line items with quantities and pricing
- **Stock** - Current stock levels per article per depot
- **StockMovement** - Audit trail of all movements
- **Reglement** - Payment records
- **Article, Family, Depot, Transporteur, CompteT** - Master data

### Services

- **StockMovementService** - Handles all automatic stock adjustments
    - Processes movements on document creation/update/delete
    - Manages reversals for edits
    - Creates audit trail entries

### Controllers

- **DocumentController** - CRUD operations for documents with automatic stock handling
- **StockController** - Stock level management and manual adjustments
- **ReglementController** - Payment tracking with automatic status updates

## Data Flow Diagrams

### Document Creation Flow

```
User creates Document
    ↓
DocumentController.store()
    ↓
Create Document + Lines
    ↓
StockMovementService.processDocumentMovement()
    ↓
Calculate movement type (IN/OUT based on document type)
    ↓
Update f_stock table
    ↓
Create StockMovement record (audit trail)
    ↓
Return success message
```

### Payment Flow

```
User adds Reglement (Payment)
    ↓
ReglementController.store()
    ↓
Create Reglement record
    ↓
syncDocumentPayment()
    ↓
Calculate total paid from validated reglements
    ↓
Update document.do_montant_regle
    ↓
Set document.do_statut (0/1/2 based on amount paid)
    ↓
Return success
```

## Reporting & Views

### Document Detail Page (`/documents/{id}`)

Shows:

- Document information (type, date, customer, depot)
- Line items with pricing
- Payment summary (total, paid, remaining)
- Payment history with modes and dates
- Stock movements triggered by this document
- Shipment status with ability to update

### Stock Management (`/stocks`)

- View current stock levels per depot
- Search articles by code/name
- Filter by depot
- Manual adjustments with reason tracking
- See stock movement history

### Documents List (`/documents`)

- Filter by date range, type, customer, payment status
- Quick actions (edit, duplicate, delete)
- View payment status at a glance

## Key Validations

### Current System

- Document number must be unique
- Quantities must be positive
- Articles and tiers must exist
- Dates are validated

### Recommended Additional Validations

- Prevent shipping more than current stock
- Prevent deleting fully paid invoices
- Enforce document type transitions (quote→order→delivery)
- Validate credit limits for customers

## Configuration

### Document Type Codes

Edit `DocumentController::types()` to customize labels:

```php
protected function types(): array {
    return [
        'DE' => 'Devis',
        'BC' => 'Bon de commande',
        'BL' => 'Bon de livraison',
        'FA' => 'Facture',
        'BR' => 'Bon de retour',
        'FR' => 'Facture retour',
    ];
}
```

### Payment Modes

Edit `ReglementController::modes()`:

```php
protected function modes(): array {
    return [
        1 => 'Especes',
        2 => 'Cheque',
        3 => 'Virement',
        4 => 'Effet / Traite',
    ];
}
```

### Shipment Status

Edit `DocumentController::statuts()`:

```php
protected function statuts(): array {
    return [
        'en_attente' => 'En attente',
        'en_cours' => 'En cours',
        'livre' => 'Livre',
    ];
}
```

## Database Schema

### Key Tables

- `f_articles` - Products with pricing and stock info
- `f_familles` - Product families
- `f_docentete` - Document headers (sales orders, invoices, etc.)
- `f_docligne` - Document line items
- `f_stock` - Current stock levels per article per depot
- `stock_movements` - Audit trail of all stock changes
- `f_reglements` - Payment records
- `f_comptet` - Customers and suppliers
- `f_depots` - Warehouses
- `f_transporteurs` - Carriers

## API Integration Ready

The system is structured to support REST API extensions:

- All business logic in services (not controllers)
- Complete validation using FormRequest classes
- Consistent response patterns
- Detailed audit trails for integration verification

## Future Enhancements

1. **Advanced Reporting**
    - Sales summaries by customer/period
    - Stock aging reports
    - Outstanding payment reports
    - Profit/loss by product

2. **Automation**
    - Email notifications for orders/payments
    - Automatic document numbering
    - Payment reminders
    - Stock alerts

3. **Mobile App**
    - Order management
    - Stock adjustments
    - Payment processing

4. **Advanced Features**
    - Multiple currencies
    - Tax management
    - Accounting integration
    - Automated invoicing

## Support & Maintenance

For questions or issues with the ERP system, refer to:

- Code comments in models and services
- Database migrations for schema changes
- Controller documentation for API changes
- Stock movement audit trail for transaction verification
