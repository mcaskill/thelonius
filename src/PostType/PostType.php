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

namespace Thelonius\PostType;

use Thelonius\Contracts\Support\Stringable;

/**
 * A Post Type Model
 */
class PostType implements
    Stringable
{
    /**
     * The post type identifier.
     *
     * @var string
     */
    public $name;

    /**
     * The post type attributes.
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * The array of registered post types.
     *
     * @var array
     */
    protected static $registered = [];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'posts';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'ID';

    /**
     * Create a new WordPress post type model instance.
     *
     * @param  string $name        Post type key, must not exceed 20 characters.
     * @param  array  $attributes  Array or string of arguments for registering a post type.
     * @return void
     */
    public function __construct( $name, array $attributes = [])
    {
        $this->setName( $name );
        $this->setAttributes( $attributes );

        $this->registerIfNotRegistered();
    }

    /**
     * Define the post type identifier.
     *
     * @param  string $name  Post type key, must not exceed 20 characters.
     * @return PostType
     */
    protected function setName( $name )
    {
        if ( ! is_string( $name ) ) {
            throw new InvalidArgumentException('Post type name must be a string.');
        }

        $name = sanitize_key( $name );

        if ( empty( $post_type ) || strlen( $post_type ) > 20 ) {
            throw new InvalidArgumentException(
                'Post type names must be between 1 and 20 characters in length.'
            );
        }

        $this->name = $name;

        return $this;
    }

    /**
     * Define the post type arguments.
     *
     * @param  array  $attributes  Array or string of arguments for registering a post type.
     * @return PostType
     */
    protected function setAttributes( array $attributes = [] )
    {
        $this->attributes = $attributes;

        return $this;
    }

    /**
     * Check if the post type needs to be booted and if so, do it.
     *
     * @return void
     */
    protected function registerIfNotRegistered()
    {
        if ( ! isset(static::$registered[$this->name]) ) {
            static::$registered[$this->name] = true;

            static::register();
        }
    }

    /**
     * The "registering" method of the post type.
     *
     * @return void
     */
    protected static function register()
    {
        register_post_type( $this->name, $this->attributes );
    }

    /**
     * Clear the list of registered post types so they will be registered anew.
     *
     * @return void
     */
    public static function clearRegisteredPostTypes()
    {
        static::$registered = [];
    }

    /**
     * Retrieve the post type name.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->name;
    }
}
