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
use UnexpectedValueException;
use Thelonius\Entity\Entity;
use Thelonius\Contracts\Support\Stringable;

/**
 * A Taxonomy Model
 *
 * The model class can be used either as a simple object wrapper:
 *
 * ```
 * $genres = new Taxonomy( 'bks-genre', [
 *     'label'     => 'Genres',
 *      'public'   => true,
 *      'rewrite'  => [
 *          'slug' => 'genre'
 *      ]
 * ] );
 * ```
 *
 * or extended as its own class:
 *
 * ```
 * class Books extends Taxonomy
 * {
 *     public function __construct()
 *     {
 *         $args = [
 *             'label'     => 'Genres',
 *              'public'   => true,
 *              'rewrite'  => [
 *                  'slug' => 'genre'
 *              ]
 *         ];
 *
 *         parent::__construct( 'bks-genre', $args );
 *     }
 * }
 * ```
 *
 * [1]: Testing if taxonomy and post types are registered outside of {@see register_taxonomy_for_object_type()}
 *      allows for better error reporting in case that function fails.
 *
 */
class Taxonomy extends Entity implements
    Stringable
{
    /**
     * The type of model.
     *
     * @var string
     */
    const ENTITY_TYPE = 'taxonomy';

    /**
     * The taxonomy identifier.
     *
     * @var string
     */
    public $name;

    /**
     * The taxonomy attributes.
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * The post type relationships.
     *
     * @var array
     */
    protected $relations = [];

    /**
     * The registered taxonomy object, or an error object.
     *
     * @var object|WP_Error
     */
    protected $object;

    /**
     * The array of registered taxonomies.
     *
     * @var array
     */
    protected static $registered = [];

    /**
     * Create a new WordPress taxonomy model instance.
     *
     * @param  string $name        Taxonomy key, must not exceed 20 characters.
     * @param  array  $relations   Name of one or more post types for the taxonomy object.
     * @param  array  $attributes  Array of arguments for registering a taxonomy.
     * @return void
     */
    public function __construct( $name, $relations = [], array $attributes = [] )
    {
        $this->setName( $name );
        $this->setRelationships( $relations );
        $this->setAttributes( $attributes );

        $this->bootIfNotBooted();
        $this->registerIfNotRegistered();
    }

    /**
     * Define the taxonomy identifier.
     *
     * @param  string $name  Taxonomy key, must not exceed 20 characters.
     * @return Taxonomy
     */
    protected function setName( $name )
    {
        if ( ! is_string( $name ) ) {
            throw new InvalidArgumentException( 'Taxonomy name must be a string.' );
        }

        $name = sanitize_key( $name );

        if ( empty( $name ) || strlen( $name ) > 32 ) {
            throw new LengthException(
                'Taxonomy names must be between 1 and 32 characters in length.'
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
         * @todo Add exception if the taxonomy is _built-in_.
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
            # throw new DomainException( 'Dashes are not recommended for taxonomy names.' );
            trigger_error( 'Dashes are not recommended for taxonomy names.' );
        }

        $this->name = $name;

        return $this;
    }

    /**
     * Define the taxonomy arguments.
     *
     * @param  array  $attributes  Array or string of arguments for registering a taxonomy.
     * @return Taxonomy
     */
    protected function setAttributes( array $attributes = [] )
    {
        $this->attributes = $attributes;

        return $this;
    }

    /**
     * Define the taxonomy / post type relationships.
     *
     * @param  array|string  $relations  Name of one or more post types for the taxonomy object.
     * @return Taxonomy
     */
    protected function setRelationships( $relations = [] )
    {
        if ( is_string( $relations ) ) {
            $relations = [ $relations ];
        }

        if ( ! is_array( $relations ) ) {
            throw new InvalidArgumentException( 'Invalid relationship. Must be one or more post type names.' );
        }

        $this->relations = $relations;

        return $this;
    }

    /**
     * Register actions related to the taxonomy.
     *
     * @return void
     */
    public function registerActions()
    {
        add_action( 'init', [ $this, 'register' ] );
    }

    /**
     * Check if the taxonomy needs to be booted and if so, do it.
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
     * The "registering" method of the taxonomy.
     *
     * Do not use before the "init" action.
     *
     * @return void
     */
    public function register()
    {
        $this->object = register_taxonomy( $this->name, $this->relations, $this->attributes );
    }

    /**
     * Link the taxonomy to registered post types.
     *
     * Allow the taxonomy to be found in 'parse_query' or 'pre_get_posts' filters.
     *
     * @link http://codex.wordpress.org/Function_Reference/register_taxonomy_for_object_type
     *
     * @return void
     */
    public function bind()
    {
        foreach ( $this->relations as $post_type ) {
            /** [1] */
            if ( ! get_taxonomy( $this->name ) ) {
                throw new UnexpectedValueException(
                    sprintf( 'Taxonomy "%s" must be registered.', $this->name )
                );
            }

            /** [1] */
            if ( ! get_post_type_object( $post_type ) ) {
                throw new UnexpectedValueException(
                    sprintf( 'Post type "%s" must be registered.', $post_type )
                );
            }

            if ( ! register_taxonomy_for_object_type($this->name, $post_type) ) {
                trigger_error(
                    sprintf(
                        'Taxonomy "%1$s" could not be bound to post type "%2$s".',
                        $this->name,
                        $post_type
                    )
                );
            }
        }

        return $this;
    }

    /**
     * Determine if the taxonomy is registered.
     *
     * @return boolean
     */
    public function isRegistered()
    {
        return taxonomy_exists( $this->name );
    }

    /**
     * Retrieve the taxonomy's registered attributes.
     *
     * @return boolean
     */
    public function getRegistration()
    {
        if ( ! isset( $this->object ) ) {
            $this->object = get_taxonomy( $this->name );
        }

        return $this->object;
    }

    /**
     * Clear the list of registered taxonomies so they will be registered anew.
     *
     * @return void
     */
    public static function clearRegisteredTaxonomies()
    {
        static::$booted[self::ENTITY_TYPE] = [];
        static::$registered = [];
    }

    /**
     * Retrieve the taxonomy name.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->name;
    }
}
