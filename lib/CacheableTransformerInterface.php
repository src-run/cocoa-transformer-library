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

interface CacheableTransformerInterface extends TransformerInterface
{
    /**
     * @param \DateInterval|null $expiresAfter
     *
     * @return self
     */
    public function setExpiresAfter(\DateInterval $expiresAfter = null): self;

    /**
     * Returns true if the passed string transformation is cached.
     *
     * @param string $string
     *
     * @return bool
     */
    public function isCached(string $string): bool;
}
