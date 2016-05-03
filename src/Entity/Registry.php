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

use ArrayAccess;
use Illuminate\Support\Arr;
use Thelonius\Contracts\Entity\Registry as RegistryInterface;

/**
 * A registry of WordPress objects.
 */
class Registry implements
    ArrayAccess,
    RegistryInterface
{
    /**
     * All of the configuration items.
     *
     * @var array
     */
    protected $items = [];

    /**
     * Create a new configuration repository.
     *
     * @param  array  $items
     * @return void
     */
    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    /**
     * Determine if the given post type exists.
     *
     * @param  string  $key  Name of the post type to lookup.
     * @return boolean
     */
    public function has($key)
    {
        return Arr::has($this->items, $key);
    }

    /**
     * Get the specified post type object.
     *
     * @param  string  $key      Name of the post type to retrieve.
     * @param  mixed   $default  Optional. Default value to return if the post type does not exist.
     * @return mixed
     */
    public function get($key, $default = null)
    {
        return Arr::get($this->items, $key, $default);
    }

    /**
     * Set a given post type object.
     *
     * @param  array|string  $key     Name of the post type to register or an associative array of
     *                                post types (`[ $key => $object ]`).
     * @param  mixed         $object  Optional. Attributes defining the post type.
     * @return void
     */
    public function set($key, $object = null)
    {
        if (is_array($key)) {
            foreach ($key as $innerKey => $innerValue) {
                Arr::set($this->items, $innerKey, $innerValue);
            }
        } else {
            Arr::set($this->items, $key, $object);
        }
    }

    /**
     * Get all of the post type objects.
     *
     * @return array
     */
    public function all()
    {
        return $this->items;
    }

    // =========================================================================
    // Satisfy ArrayAccess
    // =========================================================================

    /**
     * Determine if the given post type exists.
     *
     * @param  string  $key  Name of the post type to lookup.
     * @return boolean
     */
    public function offsetExists($key)
    {
        return $this->has($key);
    }

    /**
     * Get a post type object.
     *
     * @param  string  $key  Name of the post type to retrieve.
     * @return mixed
     */
    public function offsetGet($key)
    {
        return $this->get($key);
    }

    /**
     * Set a post type object.
     *
     * @param  string  $key     Name of the post type to register.
     * @param  mixed   $object  Optional. Attributes defining the post type.
     * @return void
     */
    public function offsetSet($key, $object)
    {
        $this->set($key, $object);
    }

    /**
     * Unset a post type object.
     *
     * @param  string  $key  Name of the post type to remove.
     * @return void
     */
    public function offsetUnset($key)
    {
        $this->set($key, null);
    }
}
