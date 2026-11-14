<?php
/**
 * QuickCart_CustomApi
 */
declare(strict_types=1);

namespace QuickCart\CustomApi\Api\Data;

/**
 * Product label (QuickCart label rules) as shown on product cards.
 *
 * @api
 */
interface ProductLabelInterface
{
    /**
     * Style hook: sale, new, trending or custom.
     *
     * @return string
     */
    public function getCode(): string;

    /**
     * Label text, e.g. "-12%".
     *
     * @return string
     */
    public function getText(): string;

    /**
     * Background colour (#RRGGBB).
     *
     * @return string
     */
    public function getBackgroundColor(): string;

    /**
     * Text colour (#RRGGBB).
     *
     * @return string
     */
    public function getTextColor(): string;
}
