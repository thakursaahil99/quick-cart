<?php
/**
 * QuickCart_CatalogEnhancement
 */
declare(strict_types=1);

namespace QuickCart\CatalogEnhancement\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use QuickCart\CatalogEnhancement\Model\ProductSource\SourcePool;

/**
 * Widget option list of the registered merchandising sources.
 */
class ProductSourceOptions implements OptionSourceInterface
{
    /**
     * @param SourcePool $sourcePool
     */
    public function __construct(
        private readonly SourcePool $sourcePool
    ) {
    }

    /**
     * @inheritdoc
     */
    public function toOptionArray(): array
    {
        $options = [];
        foreach ($this->sourcePool->getAll() as $code => $source) {
            $options[] = ['value' => $code, 'label' => $source->getLabel()];
        }

        return $options;
    }
}
