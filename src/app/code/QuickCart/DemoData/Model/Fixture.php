<?php
/**
 * QuickCart_DemoData
 */
declare(strict_types=1);

namespace QuickCart\DemoData\Model;

use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Component\ComponentRegistrarInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Reads the module's fixtures/ directory (catalog.json, cms/*.html, media/).
 */
class Fixture
{
    /**
     * @var array<string, mixed>|null
     */
    private ?array $catalog = null;

    /**
     * @param ComponentRegistrarInterface $componentRegistrar
     * @param File $fileDriver
     * @param Json $json
     */
    public function __construct(
        private readonly ComponentRegistrarInterface $componentRegistrar,
        private readonly File $fileDriver,
        private readonly Json $json
    ) {
    }

    /**
     * Decoded catalog.json.
     *
     * @return array<string, mixed>
     * @throws FileSystemException
     */
    public function getCatalog(): array
    {
        if ($this->catalog === null) {
            $this->catalog = (array)$this->json->unserialize($this->read('catalog.json'));
        }

        return $this->catalog;
    }

    /**
     * Contents of a fixture file.
     *
     * @param string $relativePath
     * @return string
     * @throws FileSystemException
     */
    public function read(string $relativePath): string
    {
        return $this->fileDriver->fileGetContents($this->getPath($relativePath));
    }

    /**
     * Absolute path of a fixture file.
     *
     * @param string $relativePath
     * @return string
     */
    public function getPath(string $relativePath): string
    {
        $base = (string)$this->componentRegistrar->getPath(ComponentRegistrar::MODULE, 'QuickCart_DemoData');

        return str_replace('\\', '/', $base) . '/fixtures/' . ltrim($relativePath, '/');
    }
}
