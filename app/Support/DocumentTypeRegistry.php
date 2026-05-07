<?php

namespace App\Support;

class DocumentTypeRegistry
{
    public const MODULE_SALES = 'sales';
    public const MODULE_PURCHASE = 'purchase';
    public const MODULE_STOCK = 'stock';

    public static function definitions(): array
    {
        return [
            'DE' => ['label' => 'Devis client', 'module' => self::MODULE_SALES, 'flow' => 'quote', 'movement' => 'NONE'],
            'BC' => ['label' => 'Commande client', 'module' => self::MODULE_SALES, 'flow' => 'order', 'movement' => 'NONE'],
            'BL' => ['label' => 'Bon de livraison', 'module' => self::MODULE_SALES, 'flow' => 'delivery', 'movement' => 'OUT'],
            'FA' => ['label' => 'Facture client', 'module' => self::MODULE_SALES, 'flow' => 'invoice', 'movement' => 'OUT'],
            'BA' => ['label' => 'Commande fournisseur', 'module' => self::MODULE_PURCHASE, 'flow' => 'order', 'movement' => 'NONE'],
            'FF' => ['label' => 'Facture fournisseur', 'module' => self::MODULE_PURCHASE, 'flow' => 'invoice', 'movement' => 'IN'],
            'MV' => ['label' => 'Mouvement de stock', 'module' => self::MODULE_STOCK, 'flow' => 'movement', 'movement' => 'ADJUSTMENT'],
            'AJ' => ['label' => 'Ajustement inventaire', 'module' => self::MODULE_STOCK, 'flow' => 'adjustment', 'movement' => 'ADJUSTMENT'],
            'TR' => ['label' => 'Transfert depot', 'module' => self::MODULE_STOCK, 'flow' => 'transfer', 'movement' => 'ADJUSTMENT'],
            'BR' => ['label' => 'Bon de retour client', 'module' => self::MODULE_SALES, 'flow' => 'return', 'movement' => 'IN'],
            'FR' => ['label' => 'Facture retour client', 'module' => self::MODULE_SALES, 'flow' => 'credit_note', 'movement' => 'IN'],
        ];
    }

    public static function labels(): array
    {
        return collect(self::definitions())
            ->mapWithKeys(fn (array $definition, string $code) => [$code => $definition['label']])
            ->all();
    }

    public static function codesByModule(string $module): array
    {
        return collect(self::definitions())
            ->filter(fn (array $definition) => $definition['module'] === $module)
            ->mapWithKeys(fn (array $definition, string $code) => [$code => $definition['label']])
            ->all();
    }

    public static function moduleFromCode(string $code): string
    {
        return self::definitions()[$code]['module'] ?? self::MODULE_SALES;
    }

    public static function movementFromCode(string $code): string
    {
        return self::definitions()[$code]['movement'] ?? 'NONE';
    }
}
