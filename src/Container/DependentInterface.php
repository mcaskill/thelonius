<?php

/*
 * This file is part of the Thelonius framework.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @link      https://github.com/mcaskill/thelonius
 * @copyright Copyright © 2016 Chauncey McAskill
 * @license   https://github.com/mcaskill/thelonius/blob/master/LICENSE (MIT License)
 */

namespace Thelonius\Container;

use Interop\Container\ContainerInterface;
use Pimple\Container;

/**
 * Defines an object with dependencies from a DI container.
 */
interface DependentInterface
{
    /**
     * Inject dependencies from a Pimple Container.
     *
     * @param  Container $container A dependencies container instance.
     * @return self
     */
    public function setDependencies(Container $container);
}
