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

namespace Thelonius\Entity;

/**
 * A Generic Model
 */
class Entity
{
    /**
     * The type of model.
     *
     * @var string
     */
    const ENTITY_TYPE = 'entity';

    /**
     * The array of booted post types.
     *
     * @var array
     */
    protected static $booted = [];

    /**
     * Create a new Thelonius entity instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->bootIfNotBooted();
    }

    /**
     * Check if the model needs to be booted and if so, do it.
     *
     * @return void
     */
    protected function bootIfNotBooted()
    {
        if ( ! isset(static::$booted[static::ENTITY_TYPE][static::class]) ) {
            static::$booted[static::ENTITY_TYPE][static::class] = true;

            /** @todo Document action */
            do_action_ref_array('thelonius/' . static::ENTITY_TYPE . '/booting', [ &$this ]);

            $this->boot();

            /** @todo Document action */
            do_action_ref_array('thelonius/' . static::ENTITY_TYPE . '/booted', [ &$this ]);
        }
    }

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    protected function boot()
    {
        $this->bootTraits();

        $this->registerActions();
        $this->registerFilters();
    }

    /**
     * Boot all of the bootable traits on the model.
     *
     * @return void
     */
    protected function bootTraits()
    {
        $class = get_called_class();

        foreach ( class_uses_recursive($class) as $trait ) {
            $method = 'boot'.class_basename($trait);

            if ( method_exists( $this, $method ) ) {
                call_user_func( [ $this, $method ] );
            }
        }
    }

    /**
     * Register actions related to the post type.
     *
     * @return void
     */
    public function registerActions()
    {
    }

    /**
     * Register filters related to the post type.
     *
     * @return void
     */
    public function registerFilters()
    {
    }

    /**
     * Clear the list of booted entities so they will be booted anew.
     *
     * @return void
     */
    public static function clearBootedEntities()
    {
        static::$booted = [];
    }
}
