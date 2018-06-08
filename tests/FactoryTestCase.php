<?php
/**
 * This file is part of Railt package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare(strict_types=1);

namespace Railt\Tests\Io;

use Railt\Io\DeclarationInterface;
use Railt\Io\Exception\NotFoundException;
use Railt\Io\Exception\NotReadableException;
use Railt\Io\File;
use Railt\Io\PositionInterface;
use Railt\Io\Readable;

/**
 * Class FactoryTestCase
 */
class FactoryTestCase extends TestCase
{
    /**
     * @return array
     */
    public function provider(): array
    {
        return [
            'Sources'        => [function () {
                return File::fromSources($this->read());
            }],
            'Pathname'       => [function () {
                return File::fromPathname(__FILE__);
            }],
            'Clone sources'  => [function () {
                return File::fromReadable(File::fromSources($this->read()));
            }],
            'Clone pathname' => [function () {
                return File::fromReadable(File::fromPathname(__FILE__));
            }],
            'SplFileInfo'    => [function () {
                return File::fromSplFileInfo(new \SplFileInfo(__FILE__));
            }],
            'SplFileObject'  => [function () {
                return File::fromSplFileInfo(new \SplFileObject(__FILE__));
            }],
        ];
    }

    /**
     * @return string
     */
    private function read(): string
    {
        return \file_get_contents(__FILE__);
    }

    /**
     * @dataProvider provider
     * @param \Closure $factory
     */
    public function testSources(\Closure $factory): void
    {
        $readable = $factory();

        $this->assertSame($this->read(), $readable->getContents());
        $this->assertSame($this->read(), (clone $readable)->getContents());
        $this->assertSame($this->read(), \unserialize(\serialize($readable))->getContents());
    }

    /**
     * @dataProvider provider
     * @param \Closure $factory
     */
    public function testHashSize(\Closure $factory): void
    {
        $readable = $factory();

        $this->assertEquals(40, \strlen($readable->getHash()));
        $this->assertEquals(40, \mb_strlen($readable->getHash()));
    }

    /**
     * @dataProvider provider
     * @param \Closure $factory
     */
    public function testHashIsConstant(\Closure $factory): void
    {
        $readable = $factory();

        $this->assertEquals($readable->getHash(), $factory()->getHash());
        $this->assertEquals($readable->getHash(), (clone $readable)->getHash());
        $this->assertEquals($readable->getHash(), \unserialize(\serialize($readable))->getHash());
    }

    /**
     * @dataProvider provider
     * @param \Closure $factory
     */
    public function testPathname(\Closure $factory): void
    {
        $readable = $factory();

        $requiredPathname = $readable->isFile() ? __FILE__ : 'php://input';

        $this->assertEquals($requiredPathname, $readable->getPathname());
        $this->assertEquals($requiredPathname, (clone $readable)->getPathname());
        $this->assertEquals($requiredPathname, \unserialize(\serialize($readable))->getPathname());
    }

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

        //
        @\chmod($file, 0777);
        @\unlink($file);
    }

    /**
     * @dataProvider provider
     * @param \Closure $factory
     */
    public function testDeclarationClass(\Closure $factory): void
    {
        /** @var Readable $readable */
        $readable = $factory();

        /** @var DeclarationInterface $declaration */
        $declaration = $readable->getDeclarationInfo();

        $this->assertEquals(__CLASS__, $declaration->getClass());
    }

    /**
     * @dataProvider provider
     * @param \Closure $factory
     */
    public function testDeclarationFile(\Closure $factory): void
    {
        /** @var Readable $readable */
        $readable = $factory();

        /** @var DeclarationInterface $declaration */
        $declaration = $readable->getDeclarationInfo();

        $this->assertEquals(__FILE__, $declaration->getPathname());
    }

    /**
     * @dataProvider provider
     * @param \Closure $factory
     * @throws \ReflectionException
     */
    public function testDeclarationLine(\Closure $factory): void
    {
        /** @var Readable $readable */
        $readable = $factory();

        $provider = new \ReflectionMethod(static::class, 'provider');

        /** @var DeclarationInterface $declaration */
        $declaration = $readable->getDeclarationInfo();

        $this->assertGreaterThan($provider->getStartLine(), $declaration->getLine());
        $this->assertLessThan($provider->getEndLine(), $declaration->getLine());
    }

    /**
     * @dataProvider provider
     * @param \Closure $factory
     * @throws \Exception
     */
    public function testPosition(\Closure $factory): void
    {
        /** @var Readable $readable */
        $readable = $factory();

        $size = \strlen($this->read());

        for ($offset = 0; $offset < $size; $offset += $size / \random_int(20, 1000)) {
            $offset = (int)\round($offset);
            $chunk  = \substr($this->read(), 0, $offset);

            /** @var PositionInterface $position */
            $position = $readable->getPosition($offset);

            $this->assertEquals(\substr_count($chunk, "\n") + 1, $position->getLine());
            $this->assertGreaterThan(0, $position->getColumn());

            if (\method_exists($position, 'getOffset')) {
                $this->assertEquals($offset, $position->getOffset());
            }
        }
    }

    /**
     * @dataProvider provider
     * @param \Closure $factory
     */
    public function testRenderable(\Closure $factory): void
    {
        $this->assertEquals($this->read(), (string)$factory());
        $this->assertEquals($this->read(), (string)$factory()->getContents());
    }
}
