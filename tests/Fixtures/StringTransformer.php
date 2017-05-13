<?php

/*
 * This file is part of the `src-run/cocoa-transformer-library` project.
 *
 * (c) Rob Frawley 2nd <rmf@src.run>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace SR\Cocoa\Transformer\Tests\Fixtures;

use SR\Cocoa\Transformer\AbstractCacheableTransformer;

class StringTransformer extends AbstractCacheableTransformer
{
    /**
     * @param string $string
     *
     * @return string
     */
    protected function runTransformation(string $string): string
    {
        return sprintf('transformed=[%s]', $string);
    }
}
