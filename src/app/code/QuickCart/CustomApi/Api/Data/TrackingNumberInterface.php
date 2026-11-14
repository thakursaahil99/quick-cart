<?php
/**
 * QuickCart_CustomApi
 */
declare(strict_types=1);

namespace QuickCart\CustomApi\Api\Data;

/**
 * Carrier tracking number of a shipment.
 *
 * @api
 */
interface TrackingNumberInterface
{
    /**
     * Carrier name.
     *
     * @return string
     */
    public function getCarrier(): string;

    /**
     * Tracking number.
     *
     * @return string
     */
    public function getNumber(): string;
}
