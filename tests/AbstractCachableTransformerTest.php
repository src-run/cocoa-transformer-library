<?php

/*
 * This file is part of the `src-run/cocoa-transformer-library` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace SR\Cocoa\Transformer\tests;

use Faker\Factory;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use SR\Cocoa\Transformer\AbstractCachableTransformer;
use SR\Cocoa\Transformer\CachableTransformerInterface;
use SR\Cocoa\Transformer\Tests\Fixtures\StringTransformer;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

/**
 * Class ExceptionTest.
 */
class AbstractCachableTransformerTest extends TestCase
{
    public function testMockedCallsCached()
    {
        $provided = $this->getLoremText();
        $expected = sprintf('transformed=[%s]', $provided);
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
            ->willReturn($expected);

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
            ->with($provided)
            ->willReturn($expected);

        $transformer->transform($provided);
    }

    public function testMockedCallsNotCached()
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

    public function testSupports()
    {
        $transformer = $this->getCachableSimpleStringTransformerInstance();

        $this->assertTrue($transformer->supports('string'));
        $this->assertFalse($transformer->isCached('string'));
        $this->assertSame('transformed=[string]', $transformer->transform('string'));
        $this->assertTrue($transformer->isCached('string'));
        $this->assertFalse($transformer->supports('not-string'));
    }

    public function testTransforms()
    {
        $provided = $this->getLoremText();
        $expected = sprintf('transformed=[%s]', $provided);

        $transformer = $this->getCachableSimpleStringTransformerInstance(null, $expiresAfter = new \DateInterval('PT1S'));

        $this->assertSame($expected, $transformer->transform($provided));
    }

    public function testCaches()
    {
        $provided = $this->getLoremText();
        $transformer = $this->getCachableSimpleStringTransformerInstance(null, $expiresAfter = new \DateInterval('PT1S'));

        $this->assertFalse($transformer->isCached($provided));
        $transformer->transform($provided);
        $this->assertTrue($transformer->isCached($provided));

        sleep(2);

        $this->assertFalse($transformer->isCached($provided));
    }

    /**
     * @param int|null $maximumCharacters
     *
     * @return string
     */
    private function getLoremText(int $maximumCharacters = null): string
    {
        return Factory::create()->text($maximumCharacters ?: 200);
    }

    /**
     * @param CacheItemPoolInterface|null $cache
     * @param \DateInterval               $expiresAfter
     *
     * @return CachableTransformerInterface
     */
    private function getCachableSimpleStringTransformerInstance(CacheItemPoolInterface $cache = null, \DateInterval $expiresAfter = null): CachableTransformerInterface
    {
        return new StringTransformer($cache ?: $this->getArrayAdapterMock(), $expiresAfter);
    }

    /**
     * @param CacheItemPoolInterface|null $cache
     * @param \DateInterval|null          $expiresAfter
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|CachableTransformerInterface
     */
    private function getAbstractCachableTransformerMock(CacheItemPoolInterface $cache = null, \DateInterval $expiresAfter = null): CachableTransformerInterface
    {
        return $this
            ->getMockBuilder(AbstractCachableTransformer::class)
            ->setConstructorArgs([$cache ?: $this->getCacheItemPoolInterfaceMock(), $expiresAfter])
            ->getMockForAbstractClass();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|CacheItemPoolInterface
     */
    private function getCacheItemPoolInterfaceMock(): CacheItemPoolInterface
    {
        return $this
            ->getMockBuilder(CacheItemPoolInterface::class)
            ->getMockForAbstractClass();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ArrayAdapter
     */
    private function getArrayAdapterMock(): ArrayAdapter
    {
        return $this
            ->getMockBuilder(ArrayAdapter::class)
            ->getMockForAbstractClass();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|CacheItemInterface
     */
    private function getCacheItemInterfaceMock(): CacheItemInterface
    {
        return $this
            ->getMockBuilder(CacheItemInterface::class)
            ->getMockForAbstractClass();
    }
}
