<?php
/**
 * QuickCart_CatalogEnhancement
 */
declare(strict_types=1);

namespace QuickCart\CatalogEnhancement\Model\ProductSource;

use InvalidArgumentException;
use Magento\Framework\Exception\NoSuchEntityException;
use QuickCart\CatalogEnhancement\Api\ProductSourceInterface;

/**
 * Registry of merchandising sources, configured through etc/di.xml.
 */
class SourcePool
{
    /**
     * @var array<string, ProductSourceInterface>
     */
    private array $sources = [];

    /**
     * @param ProductSourceInterface[] $sources
     */
    public function __construct(array $sources = [])
    {
        foreach ($sources as $code => $source) {
            if (!$source instanceof ProductSourceInterface) {
                throw new InvalidArgumentException(
                    sprintf('Product source "%s" must implement %s', $code, ProductSourceInterface::class)
                );
            }
            $this->sources[(string)$code] = $source;
        }
    }

    /**
     * Get a source by code.
     *
     * @param string $code
     * @return ProductSourceInterface
     * @throws NoSuchEntityException
     */
    public function get(string $code): ProductSourceInterface
    {
        if (!isset($this->sources[$code])) {
            throw new NoSuchEntityException(__('Product source "%1" is not registered.', $code));
        }

        return $this->sources[$code];
    }

    /**
     * All registered sources keyed by code.
     *
     * @return array<string, ProductSourceInterface>
     */
    public function getAll(): array
    {
        return $this->sources;
    }
}
