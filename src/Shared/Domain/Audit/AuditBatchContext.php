<?php

declare(strict_types=1);

namespace Src\Shared\Domain\Audit;

/**
 * The kind of bulk operation a batch represents. Extend as new bulk flows appear
 * (e.g. marketplace order import in a future version). Used for filtering the batch
 * list in the admin observability screen.
 */
enum AuditBatchContext: string
{
    case STOCK_BULK_ADJUSTMENT = 'stock_bulk_adjustment';
    case PRODUCT_IMPORT = 'product_import';
    case ORDER_IMPORT = 'order_import';
}
