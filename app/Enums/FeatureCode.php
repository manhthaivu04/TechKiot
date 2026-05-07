<?php

namespace App\Enums;

/**
 * Canonical feature codes used throughout the system.
 * Using a backed enum keeps them type-safe and IDE-friendly.
 */
enum FeatureCode: string
{
    // Basic package features
    case MENU_MANAGEMENT = 'MENU_MANAGEMENT';
    case POS_QUICK_ORDER = 'POS_QUICK_ORDER';
    case QR_STATIC_ORDER = 'QR_STATIC_ORDER';
    case DAILY_REVENUE   = 'DAILY_REVENUE';

    // Pro package features (in addition to Basic)
    case TABLE_MANAGEMENT = 'TABLE_MANAGEMENT';
    case STAFF_MANAGEMENT = 'STAFF_MANAGEMENT';
    case QR_TABLE_ORDER   = 'QR_TABLE_ORDER';
    case DETAIL_REPORT    = 'DETAIL_REPORT';

    // Premium package features (in addition to Pro)
    case INVENTORY_MANAGEMENT = 'INVENTORY_MANAGEMENT';
    case RECIPE_MANAGEMENT    = 'RECIPE_MANAGEMENT';
    case STOCK_AUDIT          = 'STOCK_AUDIT';
}
