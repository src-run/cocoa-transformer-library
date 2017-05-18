<?php

/*
 * This file is part of the `src-run/cocoa-transformer-library` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace SR\Cocoa\Transformer;

use Psr\Cache\CacheItemPoolInterface;

abstract class AbstractCacheableTransformer extends AbstractTransformer implements CacheableTransformerInterface
{
    /**
     * @var CacheItemPoolInterface
     */
    private $cache;

    /**
     * @var \DateInterval
     */
    private $expiresAfter;

    /**
     * @param CacheItemPoolInterface $cache
     * @param \DateInterval|null     $expiresAfter
     */
    public function __construct(CacheItemPoolInterface $cache, \DateInterval $expiresAfter = null)
    {
        $this->cache = $cache;
        $this->setExpiresAfter($expiresAfter);
    }

    /**
     * @param \DateInterval|null $expiresAfter
     *
     * @return CacheableTransformerInterface
     */
    public function setExpiresAfter(\DateInterval $expiresAfter = null): CacheableTransformerInterface
    {
        $this->expiresAfter = $expiresAfter;

        return $this;
    }

    /**
     * Transform the provided string into another format.
     *
     * @param string $string
     *
     * @return string
     */
    public function transform(string $string): string
    {
        $item = $this->cache->getItem($this->generateCacheKey($string));

        if (false === $item->isHit()) {
            $item->set($this->runTransformation($string));

            if (null !== $this->expiresAfter) {
                $item->expiresAfter($this->expiresAfter);
            }

            $this->cache->save($item);
        }

        return $item->get();
    }

    /**
     * Returns true if the passed string transformation is cached.
     *
     * @param string $string
     *
     * @return bool
     */
    public function isCached(string $string): bool
    {
        return $this->cache->getItem($this->generateCacheKey($string))->isHit();
    }

    /**
     * @param string $string
     *
     * @return string
     */
    private function generateCacheKey(string $string): string
    {
        return sprintf('%s_%s', str_replace('\\', '-', get_called_class()), hash('sha256', $string));
    }

    /**
     * @param string $string
     *
     * @return string
     */
    abstract protected function runTransformation(string $string): string;
}
