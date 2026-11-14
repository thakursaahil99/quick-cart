<?php
/**
 * QuickCart_CustomApi
 */
declare(strict_types=1);

namespace QuickCart\CustomApi\Api\Data;

/**
 * Configurable option the shopper chooses (e.g. Colour: Midnight, Silver).
 *
 * @api
 */
interface ProductOptionInterface
{
    /**
     * Attribute code.
     *
     * @return string
     */
    public function getAttributeCode(): string;

    /**
     * Option label.
     *
     * @return string
     */
    public function getLabel(): string;

    /**
     * Available values.
     *
     * @return string[]
     */
    public function getValues(): array;
}
