<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * Warehouse and company name constants.
 */
final class WarehouseConstants
{
    public const GOODS_IN_TRANSIT_FI = 'Goods In Transit - FI';

    public const GOODS_IN_TRANSIT_LOWER = 'Goods in Transit - FI';

    public const FINISHED_GOODS_FI = 'Finished Goods - FI';

    public const P2_CONSIGNMENT_PARENT = 'P2 Consignment Warehouse - FI';

    public const COMPANY_FUMACO = 'FUMACO Inc.';

    /**
     * Parent warehouses whose bins should not count toward Item Profile
     * Available / On Hand widgets (non-sellable or non-site stock).
     * Matched as a prefix so both "Name" and "Name - FI" are excluded.
     *
     * @var list<string>
     */
    public const ITEM_PROFILE_STOCK_SUMMARY_EXCLUDED_PARENTS = [
        'P2 Consignment Warehouse',
        'Assembly Warehouse',
        'Reject Warehouse',
    ];

    public static function isExcludedFromItemProfileStockSummary(?string $parentWarehouse, ?string $warehouse = null): bool
    {
        return self::matchesExcludedStockSummaryName($parentWarehouse)
            || self::matchesExcludedStockSummaryName($warehouse);
    }

    private static function matchesExcludedStockSummaryName(?string $name): bool
    {
        if ($name === null || $name === '') {
            return false;
        }

        foreach (self::ITEM_PROFILE_STOCK_SUMMARY_EXCLUDED_PARENTS as $excluded) {
            if (strcasecmp($name, $excluded) === 0) {
                return true;
            }

            if (stripos($name, $excluded) === 0) {
                return true;
            }
        }

        return false;
    }
}
