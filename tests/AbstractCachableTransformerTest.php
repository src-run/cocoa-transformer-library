<?php

/*
 * This file is part of the `src-run/cocoa-transformer-library` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace SR\Cocoa\Transformer\Tests;

use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use SR\Cocoa\Transformer\AbstractCachableTransformer;
use SR\Cocoa\Transformer\CachableTransformerInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

/**
 * Class ExceptionTest.
 */
class AbstractCachableTransformerTest extends \PHPUnit_Framework_TestCase
{
    public function testTransformerCaching()
    {
        $stringInput = 'input-string';
        $stringOutput = 'output-string';
        $expiresAfter = new \DateInterval('PT1S');

        $transformer = $this->getAbstractCachableTransformerMock($this->getArrayAdapterMock(), $expiresAfter);
        $transformer
            ->expects($this->once())
            ->method('runTransformation')
            ->with($stringInput)
            ->willReturn($stringOutput);

        $this->assertFalse($transformer->isCached($stringInput));
        $transformer->transform($stringInput);
        $this->assertTrue($transformer->isCached($stringInput));

        sleep(2);

        $this->assertFalse($transformer->isCached($stringInput));
    }

    public function testTransformerMockedCached()
    {
        $stringInput = 'input-string';
        $stringOutput = 'output-string';
        $expiresAfter = new \DateInterval('PT1S');

        $item = $this->getCacheItemInterfaceMock();
        $item
            ->expects($this->any())
            ->method('isHit')
            ->willReturn(false);

        $item
            ->expects($this->once())
            ->method('set')
            ->withAnyParameters()
            ->willReturn($item);

        $item
            ->expects($this->once())
            ->method('expiresAfter')
            ->with($expiresAfter);

        $item
            ->expects($this->once())
            ->method('get')
            ->willReturn($stringOutput);

        $cache = $this->getCacheItemPoolInterfaceMock();
        $cache
            ->expects($this->any())
            ->method('getItem')
            ->withAnyParameters()
            ->willReturn($item);

        $cache
            ->expects($this->once())
            ->method('save')
            ->withAnyParameters()
            ->willReturn($item);

        $transformer = $this->getAbstractCachableTransformerMock($cache, $expiresAfter);
        $transformer
            ->expects($this->once())
            ->method('runTransformation')
            ->with($stringInput)
            ->willReturn($stringOutput);

        $transformer->transform($stringInput);
    }

    public function testTransformerMockedNotCached()
    {
        $stringInput = 'input-string';
        $stringOutput = 'output-string';
        $expiresAfter = new \DateInterval('PT1S');

        $item = $this->getCacheItemInterfaceMock();
        $item
            ->expects($this->any())
            ->method('isHit')
            ->willReturn(true);

        $item
            ->expects($this->never())
            ->method('set');

        $item
            ->expects($this->never())
            ->method('expiresAfter');

        $item
            ->expects($this->once())
            ->method('get')
            ->willReturn($stringOutput);

        $cache = $this->getCacheItemPoolInterfaceMock();
        $cache
            ->expects($this->any())
            ->method('getItem')
            ->withAnyParameters()
            ->willReturn($item);

        $cache
            ->expects($this->never())
            ->method('save');

        $transformer = $this->getAbstractCachableTransformerMock($cache, $expiresAfter);
        $transformer
            ->expects($this->never())
            ->method('runTransformation');

        $transformer->transform($stringInput);
    }

    public function testArrayTransformer()
    {
        $transformer = new StringTransformer($this->getArrayAdapterMock());

        $this->assertTrue($transformer->supports('string'));
        $this->assertFalse($transformer->isCached('string'));
        $this->assertSame('transformed:string', $transformer->transform('string'));
        $this->assertTrue($transformer->isCached('string'));
        $this->assertFalse($transformer->supports('not-string'));
    }

    /**
     * @param CacheItemPoolInterface|null $cache
     * @param \DateInterval|null $expiresAfter
     * @return \PHPUnit_Framework_MockObject_MockObject|CachableTransformerInterface
     */
    private function getAbstractCachableTransformerMock(CacheItemPoolInterface $cache = null, \DateInterval $expiresAfter = null)
    {
        return $this
            ->getMockBuilder(AbstractCachableTransformer::class)
            ->setConstructorArgs([$cache ?: $this->getCacheItemPoolInterfaceMock(), $expiresAfter])
            ->getMockForAbstractClass();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|CacheItemPoolInterface
     */
    private function getCacheItemPoolInterfaceMock()
    {
        return $this
            ->getMockBuilder(CacheItemPoolInterface::class)
            ->getMockForAbstractClass();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ArrayAdapter
     */
    private function getArrayAdapterMock()
    {
        return $this
            ->getMockBuilder(ArrayAdapter::class)
            ->getMockForAbstractClass();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|CacheItemInterface
     */
    private function getCacheItemInterfaceMock()
    {
        return $this
            ->getMockBuilder(CacheItemInterface::class)
            ->getMockForAbstractClass();
    }
}

class StringTransformer extends AbstractCachableTransformer
{
    protected function runTransformation(string $string): string
    {
        return 'transformed:'.$string;
    }
}
