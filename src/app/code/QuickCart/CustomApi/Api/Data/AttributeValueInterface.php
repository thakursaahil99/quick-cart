<?php
/**
 * QuickCart_CustomApi
 */
declare(strict_types=1);

namespace QuickCart\CustomApi\Api\Data;

/**
 * A product specification (attribute code, store label and display value).
 *
 * @api
 */
interface AttributeValueInterface
{
    /**
     * Attribute code.
     *
     * @return string
     */
    public function getCode(): string;

    /**
     * Store label.
     *
     * @return string
     */
    public function getLabel(): string;

    /**
     * Display value (option labels resolved).
     *
     * @return string
     */
    public function getValue(): string;
}
