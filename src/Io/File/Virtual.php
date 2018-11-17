<?php
/**
 * This file is part of Railt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Railt\Io\File;

/**
 * Class Virtual
 */
class Virtual extends BaseFile
{
    /**
     * @var string A default file name which created from sources
     */
    private const FILE_NAME = 'php://input';

    /**
     * @var string|null
     */
    private $hash;

    /**
     * Virtual constructor.
     * @param string $contents
     * @param string|null $name
     */
    public function __construct(string $contents, string $name = null)
    {
        parent::__construct($contents, $name ?? self::FILE_NAME);
    }

    /**
     * @return string
     */
    public function getHash(): string
    {
        if ($this->hash === null) {
            $this->hash = \sha1($this->getContents());
        }

        return $this->hash;
    }

    /**
     * @return bool
     */
    public function isFile(): bool
    {
        return false;
    }
}
