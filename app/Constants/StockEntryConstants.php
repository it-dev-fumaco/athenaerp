<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * Stock Entry and related document purpose, status, and type constants.
 * Centralizes magic strings used across MainController and services.
 */
final class StockEntryConstants
{
    // Stock Entry purpose
    public const PURPOSE_MATERIAL_ISSUE = 'Material Issue';

    public const PURPOSE_MATERIAL_TRANSFER = 'Material Transfer';

    public const PURPOSE_MATERIAL_RECEIPT = 'Material Receipt';

    public const PURPOSE_MATERIAL_TRANSFER_FOR_MANUFACTURE = 'Material Transfer for Manufacture';

    public const PURPOSE_MANUFACTURE = 'Manufacture';

    // Item / detail status
    public const STATUS_FOR_CHECKING = 'For Checking';

    public const STATUS_ISSUED = 'Issued';

    public const STATUS_RETURNED = 'Returned';

    public const STATUS_FOR_RETURN = 'For Return';

    public const STATUS_RECEIVED = 'Received';

    public const STATUS_PENDING_TO_RECEIVE = 'Pending to Receive';

    // Transfer type (transfer_as)
    public const TRANSFER_AS_CONSIGNMENT = 'Consignment';

    public const TRANSFER_AS_FOR_RETURN = 'For Return';

    public const TRANSFER_AS_INTERNAL_TRANSFER = 'Internal Transfer';

    public const TRANSFER_AS_SAMPLE_ITEM = 'Sample Item';

    public const TRANSFER_AS_STORE_TRANSFER = 'Store Transfer';

    // Issue type (issue_as)
    public const ISSUE_AS_CUSTOMER_REPLACEMENT = 'Customer Replacement';

    public const ISSUE_AS_SAMPLE = 'Sample';

    // Receive type (receive_as)
    public const RECEIVE_AS_SALES_RETURN = 'Sales Return';

    // Naming series
    public const NAMING_SERIES_STEC = 'STEC-';

    // Reference types
    public const REFERENCE_STOCK_ENTRY = 'Stock Entry';

    public const REFERENCE_DELIVERY_NOTE = 'Delivery Note';

    public const REFERENCE_PACKING_SLIP = 'Packing Slip';

    public const REFERENCE_PICKING_SLIP = 'Picking Slip';
}
