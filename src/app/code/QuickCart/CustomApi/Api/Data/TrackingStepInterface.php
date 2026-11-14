<?php
/**
 * QuickCart_CustomApi
 */
declare(strict_types=1);

namespace QuickCart\CustomApi\Api\Data;

/**
 * One step of the delivery progress bar.
 *
 * @api
 */
interface TrackingStepInterface
{
    /**
     * Milestone code.
     *
     * @return string
     */
    public function getCode(): string;

    /**
     * Label.
     *
     * @return string
     */
    public function getLabel(): string;

    /**
     * done, current or upcoming.
     *
     * @return string
     */
    public function getState(): string;

    /**
     * When the step was reached (ISO 8601), or null.
     *
     * @return string|null
     */
    public function getReachedAt(): ?string;
}
