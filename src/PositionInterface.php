<?php
/**
 * This file is part of Railt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Railt\Io;

/**
 * Interface PositionInterface
 */
interface PositionInterface
{
    /**
     * NOTE: Do not use the returned typehint for compatibility with exceptions.
     *
     * @return int
     */
    public function getLine();

    /**
     * NOTE: Do not use the returned typehint for compatibility with exceptions.
     *
     * @return int
     */
    public function getColumn();
}
