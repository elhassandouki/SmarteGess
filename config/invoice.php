<?php

/**
 * Invoice and Thermal Printer Ticket Configuration
 * 
 * This configuration file is used to customize the behavior of invoice generation
 * and thermal printer ticket generation across all document types.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Invoice PDF Settings
    |--------------------------------------------------------------------------
    */
    'invoice' => [
        // Enable/disable invoice PDF generation
        'enabled' => true,

        // Paper format (A4, Letter, etc.)
        'paper_format' => 'A4',

        // Paper orientation (portrait, landscape)
        'orientation' => 'portrait',

        // Show company details on invoice
        'show_company_details' => true,

        // Show customer/supplier details
        'show_customer_details' => true,

        // Show payment information
        'show_payment_info' => true,

        // Show delivery information
        'show_delivery_info' => true,

        // Include line item details
        'include_line_details' => true,

        // Show tax breakdown
        'show_tax_breakdown' => true,

        // Custom footer message
        'footer_message' => 'Merci de votre confiance',

        // Show signature area
        'show_signature_area' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Thermal Printer Ticket Settings
    |--------------------------------------------------------------------------
    */
    'thermal' => [
        // Enable/disable thermal printer tickets
        'enabled' => true,

        // Paper width in mm (typically 80mm for standard thermal printers)
        'paper_width_mm' => 80,

        // Character width (for text wrapping calculations)
        'char_width' => 12,

        // Show company logo/name
        'show_company_name' => true,

        // Show customer/supplier details
        'show_customer_details' => true,

        // Show payment status
        'show_payment_status' => true,

        // Include item details
        'include_item_details' => true,

        // Show tax information
        'show_tax_info' => true,

        // Footer message
        'footer_message' => 'Merci de votre visite',

        // Auto-cut paper (for compatible printers)
        'auto_cut' => true,

        // Partial cut (false) or full cut (true)
        'full_cut' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Document Type Support
    |--------------------------------------------------------------------------
    | Specify which document types should support invoice PDF and thermal tickets
    |
    | Types: DE (Devis), BC (Commande), BL (Bon de livraison), FA (Facture),
    |        BA (Bon d'achat), FF (Facture fournisseur), BR (Bon de retour), etc.
    */
    'supported_document_types' => [
        'DE', // Devis/Quote
        'BC', // Commande client/Order
        'BL', // Bon de livraison/Delivery
        'FA', // Facture/Invoice
        'BA', // Commande fournisseur/Purchase Order
        'FF', // Facture fournisseur/Supplier Invoice
        'BR', // Bon de retour/Return
        'FR', // Facture retour/Credit Note
    ],

    /*
    |--------------------------------------------------------------------------
    | Language Strings
    |--------------------------------------------------------------------------
    | Labels and messages for different document types
    */
    'labels' => [
        'DE' => ['singular' => 'Devis', 'plural' => 'Devis'],
        'BC' => ['singular' => 'Commande Client', 'plural' => 'Commandes Clients'],
        'BL' => ['singular' => 'Bon de Livraison', 'plural' => 'Bons de Livraison'],
        'FA' => ['singular' => 'Facture', 'plural' => 'Factures'],
        'BA' => ['singular' => 'Bon d\'Achat', 'plural' => 'Bons d\'Achat'],
        'FF' => ['singular' => 'Facture Fournisseur', 'plural' => 'Factures Fournisseur'],
        'BR' => ['singular' => 'Bon de Retour', 'plural' => 'Bons de Retour'],
        'FR' => ['singular' => 'Facture Retour', 'plural' => 'Factures Retour'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Invoice Number Format
    |--------------------------------------------------------------------------
    */
    'invoice_numbering' => [
        // Use document piece number as invoice number
        'use_document_number' => true,

        // Or generate separate invoice number
        'generate_separate_number' => false,

        // Prefix for generated invoice numbers
        'prefix' => 'INV',

        // Separator (between prefix and number)
        'separator' => '-',
    ],

    /*
    |--------------------------------------------------------------------------
    | Tax Calculation
    |--------------------------------------------------------------------------
    */
    'tax' => [
        // Calculate tax on each line
        'line_level_calculation' => true,

        // Rounding mode (floor, ceil, round)
        'rounding_mode' => 'round',

        // Number of decimal places for tax
        'decimal_places' => 2,
    ],
];
