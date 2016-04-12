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

namespace Thelonius\Database;

use InvalidArgumentException;

use WP_Error;
use WP_Query;
use WP_User;

use Illuminate\Support\Collection;

/**
 * Base repository for the WordPress Database / Query API.
 *
 * @link https://carlalexander.ca/designing-class-manage-wordpress-posts/
 *       Based on Carl Alexander's examples from "Designing a class to manage WordPress posts".
 */
class Repository
{
    /**
     * WordPress query object.
     *
     * @var WP_Query
     */
    protected $query;

    /**
     * Returns new Repository object.
     *
     * @param WP_Query  $query  A WordPress Query API instance.
     */
    public function __construct(WP_Query $query = null)
    {
        $this->query = ( $query ?: new WP_Query );
    }

    /**
     * Find a single post object for the given query.
     *
     * @param array|string  $query  Array or string of Query parameters.
     *
     * @return WP_Post|null  Returns NULL if it doesn't find one.
     */
    protected function findOne($query)
    {
        if (is_string($query)) {
            wp_parse_str($query, $query);
        }

        $query = array_merge($query, [
            'posts_per_page' => 1,
        ]);

        $posts = $this->find($query);

        return ( ! empty($posts[0]) ? $posts[0] : null );
    }

    /**
     * Find all post objects for the given query.
     *
     * @param array|string  $query  Array or string of Query parameters.
     *
     * @return WP_Post[]
     */
    protected function find(array $query)
    {
        if (is_string($query)) {
            wp_parse_str($query, $query);
        }

        $query = array_merge([
            'no_found_rows' => true,
            'update_post_meta_cache' => true,
            'update_post_term_cache' => false,
        ], $query);

        return $this->query->query($query);
    }

    /**
     * Resolve the passed variable to return an ID.
     *
     * @param mixed  $var  An integer, array, or object containing an integer as an ID.
     *
     * @return integer|null  Returns the extracted ID or NULL.
     */
    protected function resolveToID($var)
    {
        if ( is_int($var) ) {
            return $var;
        }

        if ( is_array($var) && isset($var['ID']) ) {
            return $var['ID'];
        }

        if ( is_object($var) && isset($var->ID) ) {
            return $var->ID;
        }

        throw new InvalidArgumentException(
            'Unable to resolve the ID of the passed variable. '.
            'Must be an integer or an array or object with an "ID" key, property, or method.'
        );
    }
}
