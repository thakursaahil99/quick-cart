<?php
/**
 * QuickCart_Core
 */
declare(strict_types=1);

namespace QuickCart\Core\Ui\Component\Listing\Column;

use Magento\Framework\Escaper;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Edit / Delete row actions for QuickCart admin grids.
 *
 * Configured from the listing XML through the column's "qcActions" data item:
 *   idField    - row field holding the entity ID (sent as the "id" parameter)
 *   titleField - row field used in the delete confirmation
 *   editPath   - admin route of the edit action (omit for no edit link)
 *   deletePath - admin route of the delete action (omit for no delete link)
 */
class RowActions extends Column
{
    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param Escaper $escaper
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        private readonly UrlInterface $urlBuilder,
        private readonly Escaper $escaper,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * @inheritdoc
     */
    public function prepareDataSource(array $dataSource)
    {
        $config = (array)$this->getData('qcActions');
        $idField = (string)($config['idField'] ?? 'id');
        $titleField = (string)($config['titleField'] ?? $idField);
        $editPath = (string)($config['editPath'] ?? '');
        $deletePath = (string)($config['deletePath'] ?? '');

        foreach ($dataSource['data']['items'] ?? [] as $index => $item) {
            if (!isset($item[$idField])) {
                continue;
            }
            $id = (int)$item[$idField];
            $title = $this->escaper->escapeHtml((string)($item[$titleField] ?? $id));
            $actions = [];
            if ($editPath !== '') {
                $actions['edit'] = [
                    'href' => $this->urlBuilder->getUrl($editPath, ['id' => $id]),
                    'label' => __('Edit'),
                ];
            }
            if ($deletePath !== '') {
                $actions['delete'] = [
                    'href' => $this->urlBuilder->getUrl($deletePath, ['id' => $id]),
                    'label' => __('Delete'),
                    'confirm' => [
                        'title' => __('Delete "%1"', $title),
                        'message' => __('Are you sure you want to delete "%1"?', $title),
                    ],
                    'post' => true,
                ];
            }
            $dataSource['data']['items'][$index][$this->getData('name')] = $actions;
        }

        return $dataSource;
    }
}
