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

namespace Thelonius\Contracts\Entity;

/**
 * Defines a WordPress object registry.
 */
interface Registry
{
    /**
     * Determine if the given post type exists.
     *
     * @param  string  $key  Name of the post type to lookup.
     * @return boolean
     */
    public function has($key);

    /**
     * Get the specified post type object.
     *
     * @param  string  $key      Name of the post type to retrieve.
     * @param  mixed   $default  Optional. Default value to return if the post type does not exist.
     * @return mixed
     */
    public function get($key, $default = null);

    /**
     * Set a given post type object.
     *
     * @param  array|string  $key     Name of the post type to register or an associative array of
     *                                post types (`[ $key => $object ]`).
     * @param  mixed         $object  Optional. Attributes defining the post type.
     * @return void
     */
    public function set($key, $object = null);

    /**
     * Get all of the post type objects.
     *
     * @return array
     */
    public function all();
}
