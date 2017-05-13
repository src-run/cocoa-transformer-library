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

interface TransformerInterface
{
    /**
     * Returns true if the passed type is supported by the transformer.
     *
     * @param string $type
     *
     * @return bool
     */
    public function supports(string $type): bool;

    /**
     * Transform the provided string into another format.
     *
     * @param string $string
     *
     * @return string
     */
    public function transform(string $string): string;
}
