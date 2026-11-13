<?php
/**
 * QuickCart_CatalogEnhancement
 */
declare(strict_types=1);

namespace QuickCart\CatalogEnhancement\Model;

/**
 * Codes of the product attributes owned by this module.
 */
class Attributes
{
    public const BRAND = 'qc_brand';
    public const COLOR = 'qc_color';
    public const RAM = 'qc_ram';
    public const STORAGE = 'qc_storage';
    public const DISPLAY = 'qc_display';
    public const BATTERY = 'qc_battery';
    public const CONNECTIVITY = 'qc_connectivity';
    public const WARRANTY = 'qc_warranty';
    public const IS_TRENDING = 'qc_is_trending';

    public const ATTRIBUTE_SET = 'QuickCart Electronics';
    public const GROUP_SPECIFICATIONS = 'Specifications';
    public const GROUP_MERCHANDISING = 'Merchandising';

    /**
     * Specification attributes in display order.
     */
    public const SPECIFICATIONS = [
        self::BRAND,
        self::COLOR,
        self::RAM,
        self::STORAGE,
        self::DISPLAY,
        self::BATTERY,
        self::CONNECTIVITY,
        self::WARRANTY,
    ];
}
