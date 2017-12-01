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

namespace Thelonius\Taxonomy;

use WP_Error;
use LengthException;
use DomainException;
use InvalidArgumentException;

/**
 * Represents a "built-in" Post Type model.
 *
 * This class is usefull for altering the definition of a native post type.
 */
class WordPressTaxonomy extends Taxonomy
{
    /**
     * Register actions related to the post type.
     *
     * @return void
     */
    public function registerActions()
    {
        parent::registerActions();

        add_action(
            "registered_{$this->name}_taxonomy",
            [ $this, 'registeredTaxonomy' ],
            10,
            2
        );
    }

    /**
     * The "registering" method of the post type.
     *
     * Do not use before the "init" action.
     *
     * @return void
     */
    public function register()
    {
        $this->object = null;
    }

    /**
     * Fires after a specific post type is registered.
     *
     * @used-by Action: "registered_{$taxonomy}_taxonomy" documented in
     *     vendor/thelonius/framework/events.php
     *
     * @param  array|string  $object_type  Object type or array of object types.
     * @param  array         $args         Array of taxonomy registration arguments.
     */
    public function registeredTaxonomy( $object_type, $args )
    {
        $this->object = $args;
    }
}
