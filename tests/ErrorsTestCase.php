<?php
/**
 * This file is part of Railt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Railt\Tests\Io;

use Railt\Io\Exception\ExternalExceptionInterface;
use Railt\Io\Exception\NotFoundException;
use Railt\Io\Exception\NotReadableException;
use Railt\Io\File;
use Railt\Io\Readable;

/**
 * Class ErrorsTestCase
 */
class ErrorsTestCase extends TestCase
{
    /**
     * @return void
     */
    public function testNotFound(): void
    {
        $file = __DIR__ . '/not-exists.txt';

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('File "' . $file . '" not found');

        File::fromPathname($file);
    }

    /**
     * @return void
     */
    public function testNotReadable(): void
    {
        $file = __DIR__ . '/.locked';

        $this->expectException(NotReadableException::class);
        $this->expectExceptionMessage('Can not read the file "' . $file . '": Permission denied');

        \file_put_contents($file, '');
        \chmod($file, 0333);

        File::fromPathname($file);

        @\chmod($file, 0777);
        @\unlink($file);
    }

    /**
     * @dataProvider provider
     * @param \Closure $factory
     * @return void
     * @throws \Exception
     */
    public function testExternalErrorWithLineAndColumn(\Closure $factory): void
    {
        $this->expectException(ExternalExceptionInterface::class);
        $this->expectExceptionMessage($message = 'Something went wrong ' . \random_int(\PHP_INT_MIN, \PHP_INT_MAX));

        /** @var Readable $readable */
        $readable = $factory();

        try {
            throw $readable->error($message, 23, 42);
        } catch (ExternalExceptionInterface $e) {
            $this->assertEquals(23, $e->getLine());
            $this->assertEquals(42, $e->getColumn());

            throw $e;
        }
    }

    /**
     * @dataProvider provider
     * @param \Closure $factory
     * @return void
     * @throws \Exception
     */
    public function testExternalErrorWithOffset(\Closure $factory): void
    {
        $this->expectException(ExternalExceptionInterface::class);
        $this->expectExceptionMessage($message = 'Something went wrong ' . \random_int(\PHP_INT_MIN, \PHP_INT_MAX));

        /** @var Readable $readable */
        $readable = $factory();

        try {
            throw $readable->error($message, 666);
        } catch (ExternalExceptionInterface $e) {
            $this->assertEquals(30, $e->getLine());
            $this->assertEquals(49, $e->getColumn());

            throw $e;
        }
    }

    /**
     * @return string
     */
    protected function getPathname(): string
    {
        return __FILE__;
    }
}
