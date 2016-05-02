<?php

/**
 * Extra Actions & Filters
 *
 * For making certain post type and taxonomy related hooks easier to interact with.
 */

namespace Thelonius;

add_action( 'registered_post_type',    __NAMESPACE__ . '\\registered_post_type',    10, 2 );
add_filter( 'register_post_type_args', __NAMESPACE__ . '\\register_post_type_args', 10, 2 );

/**
 * Fires after a post type is registered.
 *
 * @used-by Action: 'registered_post_type'
 *
 * @param string $post_type Post type key.
 * @param array  $args      Arguments used to register the post type.
 */
function registered_post_type( $post_type, $args )
{
    /**
     * Fires after a specific post type is registered.
     *
     * The dynamic portion of the hook name, `$post_type`, refers to the post type slug.
     *
     * @param array  $args  Arguments used to register the post type.
     */
    return do_action( "registered_{$post_type}_post_type", $args );
}

/**
 * Filter the arguments for registering a post type.
 *
 * @used-by Filter: 'register_post_type_args'
 *
 * @param array  $args      Array of arguments for registering a post type.
 * @param string $post_type Post type key.
 */
function register_post_type_args( $args, $post_type )
{
    /**
     * Filter the arguments for registering a post type for a specific post type.
     *
     * The dynamic portion of the hook name, `$post_type`, refers to the post type slug.
     *
     * @param array  $args  Array of arguments for registering a post type.
     */
    return apply_filters( "register_{$post_type}_post_type_args", $args );
}
