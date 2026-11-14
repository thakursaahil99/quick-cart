<?php
/**
 * QuickCart_CustomApi
 */
declare(strict_types=1);

namespace QuickCart\CustomApi\Model;

use Magento\Framework\Exception\InputException;

/**
 * Validates and normalises listing parameters coming from REST clients.
 */
class ProductQuery
{
    public const MAX_PAGE_SIZE = 48;
    public const MAX_SEARCH_LENGTH = 100;

    /**
     * API sort => collection attribute.
     */
    private const SORTS = [
        'position' => 'position',
        'name' => 'name',
        'price' => 'price',
        'newest' => 'created_at',
    ];

    /**
     * Normalised sort, direction and paging.
     *
     * "position" only exists inside a category; without one it falls back to newest first.
     *
     * @param string $sortBy
     * @param string $sortDir
     * @param int $page
     * @param int $pageSize
     * @param bool $inCategory
     * @return array{attribute: string, direction: string, page: int, page_size: int}
     * @throws InputException
     */
    public function normalize(string $sortBy, string $sortDir, int $page, int $pageSize, bool $inCategory): array
    {
        $sortBy = strtolower(trim($sortBy));
        if (!isset(self::SORTS[$sortBy])) {
            throw new InputException(__(
                'Invalid sortBy "%1". Use one of: %2.',
                $sortBy,
                implode(', ', array_keys(self::SORTS))
            ));
        }
        $direction = strtoupper(trim($sortDir));
        if (!in_array($direction, ['ASC', 'DESC'], true)) {
            throw new InputException(__('Invalid sortDir "%1". Use asc or desc.', $sortDir));
        }
        if ($sortBy === 'position' && !$inCategory) {
            $sortBy = 'newest';
            $direction = 'DESC';
        }

        return [
            'attribute' => self::SORTS[$sortBy],
            'direction' => $direction,
            'page' => max(1, $page),
            'page_size' => min(self::MAX_PAGE_SIZE, max(1, $pageSize)),
        ];
    }

    /**
     * Search text as a LIKE pattern with SQL wildcards in the input escaped; null when empty.
     *
     * @param string|null $search
     * @return string|null
     * @throws InputException
     */
    public function likePattern(?string $search): ?string
    {
        $search = trim((string)$search);
        if ($search === '') {
            return null;
        }
        if (mb_strlen($search) > self::MAX_SEARCH_LENGTH) {
            throw new InputException(__('search is limited to %1 characters.', self::MAX_SEARCH_LENGTH));
        }

        return '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search) . '%';
    }

    /**
     * Validate an optional price range.
     *
     * @param float|null $from
     * @param float|null $to
     * @return void
     * @throws InputException
     */
    public function assertPriceRange(?float $from, ?float $to): void
    {
        if (($from !== null && $from < 0) || ($to !== null && $to < 0)) {
            throw new InputException(__('Prices cannot be negative.'));
        }
        if ($from !== null && $to !== null && $from > $to) {
            throw new InputException(__('priceFrom must not be greater than priceTo.'));
        }
    }
}
