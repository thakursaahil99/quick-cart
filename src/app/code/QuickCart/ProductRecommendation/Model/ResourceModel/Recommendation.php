<?php
/**
 * QuickCart_ProductRecommendation
 */
declare(strict_types=1);

namespace QuickCart\ProductRecommendation\Model\ResourceModel;

use Magento\Framework\DB\Sql\Expression;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use QuickCart\ProductRecommendation\Api\Data\RecommendationInterface;

/**
 * Recommendation resource model and the bulk operations used by the rebuild.
 */
class Recommendation extends AbstractDb
{
    public const TABLE = 'quickcart_product_recommendation';

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(self::TABLE, RecommendationInterface::RECOMMENDATION_ID);
    }

    /**
     * Replace all rows of an automatic source with freshly computed ones.
     *
     * Manual rows for the same product pair keep their position / status; only the
     * score is refreshed. Runs in one transaction so the storefront never sees an
     * empty table.
     *
     * @param string $type
     * @param string $source
     * @param array<int, array{product_id: int, recommended_product_id: int, score: float}> $rows
     * @return int Rows written
     * @throws \Exception
     */
    public function replaceAutomatic(string $type, string $source, array $rows): int
    {
        $connection = $this->getConnection();
        $table = $this->getMainTable();
        $connection->beginTransaction();
        try {
            $connection->delete($table, ['type = ?' => $type, 'source = ?' => $source]);
            $data = [];
            foreach ($rows as $row) {
                $data[] = [
                    'product_id' => (int)$row['product_id'],
                    'recommended_product_id' => (int)$row['recommended_product_id'],
                    'type' => $type,
                    'source' => $source,
                    'score' => round((float)$row['score'], 4),
                    'position' => 0,
                    'is_active' => 1,
                ];
            }
            foreach (array_chunk($data, 500) as $chunk) {
                $connection->insertOnDuplicate($table, $chunk, ['score']);
            }
            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollBack();
            throw $e;
        }

        return count($data);
    }

    /**
     * Active recommended product IDs, best first: manual (by position), then by score.
     *
     * @param int $productId
     * @param string $type
     * @param int $limit
     * @return int[]
     */
    public function getRecommendedIds(int $productId, string $type, int $limit): array
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable(), ['recommended_product_id'])
            ->where('product_id = ?', $productId)
            ->where('type = ?', $type)
            ->where('is_active = ?', 1)
            ->order(new Expression(
                $connection->quoteInto('source = ?', RecommendationInterface::SOURCE_MANUAL) . ' DESC'
            ))
            ->order('position ASC')
            ->order('score DESC')
            ->order('recommended_product_id ASC')
            ->limit(max(1, $limit));

        return array_map('intval', $connection->fetchCol($select));
    }
}
