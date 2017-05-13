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

abstract class AbstractTransformer implements TransformerInterface
{
    /**
     * Returns true if the passed type is supported by the transformer.
     *
     * @param string $type
     *
     * @return bool
     */
    public function supports(string $type): bool
    {
        preg_match('{\\\(?<constraint>[A-Za-z0-9]+)Transformer}', get_called_class(), $matches);

        return isset($matches['constraint']) && strtolower($type) === strtolower($matches['constraint']);
    }
}
