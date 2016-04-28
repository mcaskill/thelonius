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

/**
 * An implementation, as Trait, of the `DependentInterface`.
 *
 * Also provides a "peer dependencies" method for calling any
 * trait with a `set<TraitName>Dependencies()` method.
 */
trait DependentTrait
{
    /**
     * Inject dependencies from a DI Container.
     *
     * @param ContainerInterface  $container  A dependencies container instance.
     *
     * @return self
     */
    public function setDependencies(ContainerInterface $container)
    {
        if (is_callable('parent::setDependencies')) {
            parent::setDependencies($container);
        }

        $this->setPeerDependencies($container);

        return $this;
    }

    /**
     * Inject peer dependencies from a DI Container for traits.
     *
     * @param ContainerInterface  $container  A dependencies container instance.
     *
     * @return self
     */
    public function setPeerDependencies(ContainerInterface $container)
    {
        $class    = get_called_class();
        $excluded = $this->excludedPeerDependencies();

        foreach (class_uses_recursive($class) as $trait) {
            if (in_array($trait, $excluded)) {
                continue;
            }

            $method = 'set'.class_basename($trait).'Dependencies';

            if (method_exists($this, $method)) {
                call_user_func([ $this, $method ], $container);
            }
        }
    }

    /**
     * Retrieve a list of traits to exclude from peer depedency setup.
     *
     * If 'DependentTrait' is not excluded, you might end up in recursive hell.
     *
     * @return array
     */
    protected function excludedPeerDependencies()
    {
        return [ 'DependentTrait' ];
    }
}
