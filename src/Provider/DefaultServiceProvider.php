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

namespace Thelonius\Provider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;

use Thelonius\Entity\Registry as EntityRegistry;

/**
 * Thelonius' default Service Provider.
 */
class DefaultServiceProvider implements ServiceProviderInterface
{
    /**
     * Register Thelonius' default services.
     *
     * @param Container $container A DI container implementing ArrayAccess and container-interop.
     */
    public function register(Container $container)
    {
        $container['post_type.registry'] = function() {
            return new EntityRegistry;
        };

        $container['post_type.features'] = function() {
            return new EntityRegistry;
        };

        $container['taxonomy.registry'] = function() {
            return new EntityRegistry;
        };
    }
}
