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

use WP_Error;
use LengthException;
use DomainException;
use InvalidArgumentException;
use Thelonius\Entity;
use Thelonius\Contracts\Support\Stringable;

/**
 * A Post Type Model
 *
 * The model class can be used either as a simple object wrapper:
 *
 * ```
 * $books = new PostType( 'bks-books', [
 *     'label'     => 'Books',
 *      'public'   => true,
 *      'rewrite'  => [
 *          'slug' => 'books'
 *      ],
 *      'supports' => [ 'title', 'editor', 'excerpt', 'thumbnail' ]
 * ] );
 * ```
 *
 * or extended as its own class:
 *
 * ```
 * class Books extends PostType
 * {
 *     const POST_TYPE = 'bks-books';
 *
 *     public function __construct()
 *     {
 *         $args = [
 *             'label'     => 'Books',
 *              'public'   => true,
 *              'rewrite'  => [
 *                  'slug' => 'books'
 *              ],
 *              'supports' => [ 'title', 'editor', 'excerpt', 'thumbnail' ]
 *         ];
 *
 *         parent::__construct( self::POST_TYPE, $args );
 *     }
 * }
 * ```
 *
 */
class PostType extends Entity implements
    Stringable
{
    /**
     * The type of model.
     *
     * @var string
     */
    const ENTITY_TYPE = 'post_type';

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
     * The custom statuses.
     *
     * @var array
     */
    protected $status = [];

    /**
     * The registered post type object, or an error object.
     *
     * @var object|WP_Error
     */
    protected $object;

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
     * @param  array  $attributes  Array of arguments for registering a post type.
     * @return void
     */
    public function __construct( $name, array $attributes = [] )
    {
        $this->setName( $name );
        $this->setAttributes( $attributes );

        $this->bootIfNotBooted();
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
            throw new InvalidArgumentException( 'Post type name must be a string.' );
        }

        $name = sanitize_key( $name );

        if ( empty( $name ) || strlen( $name ) > 20 ) {
            throw new LengthException(
                'Post type names must be between 1 and 20 characters in length.'
            );
        }

        /**
         * > Although the core development team has yet to make
         *   a final decision on this, it has been proposed on
         *   the _wp-hackers_ mailing list that future core _post type_
         *   identifiers will be namespaced with `wp_`, i.e. if the
         *   core team decides to add an _event post type_ then according
         *   to this suggestion they would use the `wp_event` identifier.
         *   Even though this has not been finalized, it will be a good
         *   idea to avoid any _custom post types_ whose identifier
         *   begins with *`wp_`*.
         * — {@link https://codex.wordpress.org/Post_Types#Reserved_Post_Type_Identifiers Reserved Post Type Identifiers}
         *
         * @todo Add exception if the post type is _built-in_.
         */
        if ( 'wp_' === substr( $name, 0, 3 ) ) {
            throw new DomainException( 'The "wp_" namespace is reserved by WordPress.' );
        }

        /**
         * Lowercase alphanumeric characters, and underscores are allowed.
         *
         * Dashes are not recommended as they can interfere with certain
         * WordPress features, such as when adding custom columns.
         */
        if ( preg_match( '/[^a-z0-9_]/', $name ) ) {
            # throw new DomainException( 'Dashes are not recommended for post type names.' );
            trigger_error( 'Dashes are not recommended for post type names.' );
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
     * Register actions related to the post type.
     *
     * @return void
     */
    public function registerActions()
    {
        add_action( 'init', [ $this, 'register' ] );
    }

    /**
     * Check if the post type needs to be booted and if so, do it.
     *
     * @return void
     */
    protected function registerIfNotRegistered()
    {
        if ( ! isset(static::$registered[$this->name]) ) {
            static::$registered[$this->name] = $this;

            if ( did_action('init') ) {
                $this->register();
            }
        }
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
        $this->object = register_post_type( $this->name, $this->attributes );
    }

    /**
     * Determine if the post type is registered.
     *
     * @return boolean
     */
    public function isRegistered()
    {
        return post_type_exists( $this->name );
    }

    /**
     * Retrieve the post type's registered attributes.
     *
     * @return boolean
     */
    public function getRegistration()
    {
        if ( ! isset( $this->object ) ) {
            $this->object = get_post_type_object( $this->name );
        }

        return $this->object;
    }

    /**
     * Clear the list of registered post types so they will be registered anew.
     *
     * @return void
     */
    public static function clearRegisteredPostTypes()
    {
        static::$booted[self::ENTITY_TYPE] = [];
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
