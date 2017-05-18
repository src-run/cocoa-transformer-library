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
use SR\Cocoa\Transformer\AbstractCacheableTransformer;
use SR\Cocoa\Transformer\CacheableTransformerInterface;
use SR\Cocoa\Transformer\Tests\Fixtures\StringTransformer;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class AbstractCacheableTransformerTest extends TestCase
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

        $transformer = $this->getAbstractCacheableTransformerMock($cache, $expiresAfter);
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

        $transformer = $this->getAbstractCacheableTransformerMock($cache, $expiresAfter);
        $transformer
            ->expects($this->never())
            ->method('runTransformation');

        $transformer->transform($stringInput);
    }

    public function testSupports()
    {
        $transformer = $this->getCacheableSimpleStringTransformerInstance();

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

        $transformer = $this->getCacheableSimpleStringTransformerInstance();

        $this->assertSame($expected, $transformer->transform($provided));
    }

    public function testCaches()
    {
        $provided = $this->getLoremText();
        $transformer = $this->getCacheableSimpleStringTransformerInstance(null, new \DateInterval('PT1S'));

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
     * @return CacheableTransformerInterface
     */
    private function getCacheableSimpleStringTransformerInstance(CacheItemPoolInterface $cache = null, \DateInterval $expiresAfter = null): CacheableTransformerInterface
    {
        return (new StringTransformer($cache ?: $this->getArrayAdapterMock()))
            ->setExpiresAfter($expiresAfter);
    }

    /**
     * @param CacheItemPoolInterface|null $cache
     * @param \DateInterval|null          $expiresAfter
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|CacheableTransformerInterface
     */
    private function getAbstractCacheableTransformerMock(CacheItemPoolInterface $cache = null, \DateInterval $expiresAfter = null): CacheableTransformerInterface
    {
        return $this
            ->getMockBuilder(AbstractCacheableTransformer::class)
            ->setConstructorArgs([$cache ?: $this->getCacheItemPoolInterfaceMock()])
            ->getMockForAbstractClass()
            ->setExpiresAfter($expiresAfter);
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
