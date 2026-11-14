<?php
/**
 * QuickCart_CustomApi
 */
declare(strict_types=1);

namespace QuickCart\CustomApi\Api\Data;

/**
 * A customer-visible timeline update.
 *
 * @api
 */
interface TrackingUpdateInterface
{
    /**
     * Milestone code.
     *
     * @return string
     */
    public function getMilestone(): string;

    /**
     * Title.
     *
     * @return string
     */
    public function getTitle(): string;

    /**
     * Message, or null.
     *
     * @return string|null
     */
    public function getComment(): ?string;

    /**
     * Location, or null.
     *
     * @return string|null
     */
    public function getLocation(): ?string;

    /**
     * Time (ISO 8601).
     *
     * @return string
     */
    public function getCreatedAt(): string;
}
