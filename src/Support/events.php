<?php

/**
 * Extra Actions & Filters
 *
 * For making certain post type and taxonomy related hooks easier to interact with.
 */

namespace Thelonius;

add_action( 'registered_post_type',    __NAMESPACE__ . '\\registered_post_type',    10, 2 );
add_filter( 'register_post_type_args', __NAMESPACE__ . '\\register_post_type_args', 10, 2 );

add_action( 'registered_taxonomy',    __NAMESPACE__ . '\\registered_taxonomy',    10, 3 );
add_filter( 'register_taxonomy_args', __NAMESPACE__ . '\\register_taxonomy_args', 10, 3 );

add_filter( 'wp_get_attachment_link',  __NAMESPACE__ . '\\attachment_link_class',   10, 6 );

/**
 * Fires after a post type is registered.
 *
 * @used-by Action: 'registered_post_type'
 *
 * @param  string  $post_type  Post type key.
 * @param  array   $args       Arguments used to register the post type.
 * @return void
 */
function registered_post_type( $post_type, $args )
{
    /**
     * Fires after a specific post type is registered.
     *
     * The dynamic portion of the hook name, `$post_type`, refers to the post type slug.
     *
     * @param array  $args  Arguments used to register the post type.
     * @return void
     */
    do_action( "registered_{$post_type}_post_type", $args );
}

/**
 * Filter the arguments for registering a post type.
 *
 * @used-by Filter: 'register_post_type_args'
 *
 * @param  array  $args      Array of arguments for registering a post type.
 * @param  string $post_type Post type key.
 * @return array  The filtered arguments for registering a post type.
 */
function register_post_type_args( $args, $post_type )
{
    /**
     * Filter the arguments for registering a specific post type.
     *
     * The dynamic portion of the hook name, `$post_type`, refers to the post type slug.
     *
     * @param  array  $args  Array of arguments for registering a post type.
     * @return array  The filtered arguments for a specific post type.
     */
    return apply_filters( "register_{$post_type}_post_type_args", $args );
}

/**
 * Fires after a taxonomy is registered.
 *
 * @used-by Action: 'registered_taxonomy'
 *
 * @param  string        $taxonomy     Taxonomy slug.
 * @param  array|string  $object_type  Object type or array of object types.
 * @param  array         $args         Array of taxonomy registration arguments.
 * @return void
 */
function registered_taxonomy( $taxonomy, $object_type, $args )
{
    /**
     * Fires after a specific taxonomy is registered.
     *
     * The dynamic portion of the hook name, `$taxonomy`, refers to the taxonomy slug.
     *
     * @param  array|string  $object_type  Object type or array of object types.
     * @param  array         $args         Array of taxonomy registration arguments.
     * @return void
     */
    do_action( "registered_{$taxonomy}_taxonomy", $object_type, $args );
}

/**
 * Filter the arguments for registering a taxonomy.
 *
 * @used-by Filter: 'register_taxonomy_args'
 *
 * @param  array   $args         Array of arguments for registering a taxonomy.
 * @param  string  $taxonomy     Taxonomy key.
 * @param  array   $object_type  Array of names of object types for the taxonomy.
 * @return array   The filtered arguments for registering a taxonomy.
 */
function register_taxonomy_args( $args, $taxonomy, $object_type )
{
    /**
     * Filter the arguments for registering a specific taxonomy.
     *
     * The dynamic portion of the hook name, `$taxonomy`, refers to the taxonomy slug.
     *
     * @param  array  $args         Array of arguments for registering a taxonomy.
     * @param  array  $object_type  Array of names of object types for the taxonomy.
     * @return array  The filtered arguments for a specific taxonomy.
     */
    return apply_filters( "register_{$taxonomy}_taxonomy_args", $args, $object_type );
}

/**
 * Add `class="thumbnail"` to attachment items
 *
 * @param  string          $link_html  The page link HTML output.
 * @param  integer         $id         Post ID.
 * @param  string          $size       Image size. Default 'thumbnail'.
 * @param  boolean         $permalink  Whether to add permalink to image. Default false.
 * @param  boolean         $icon       Whether to include an icon. Default false.
 * @param  string|boolean  $text       If string, will be link text. Default false.
 * @return string  HTML attachment page link.
 */
function attachment_link_class( $link_html, $id, $size, $permalink, $icon, $text )
{
    /**
     * Filter the HTML class attribute for an attachment page link.
     *
     * @param  string          $classes    The HTML class attribute value.
     * @param  integer         $id         Post ID.
     * @param  string          $size       Image size. Default 'thumbnail'.
     * @param  boolean         $permalink  Whether to add permalink to image. Default false.
     * @param  boolean         $icon       Whether to include an icon. Default false.
     * @param  string|boolean  $text       If string, will be link text. Default false.
     * @return string|array  CSS class names.
     */
    $classes = apply_filters( 'attachment_link_class', '', $id, $size, $permalink, $icon, $text );

    if ( ! empty( $classes ) && false === strpos( $link_html, ' class="' ) ) {
        if ( is_array( $classes ) ) {
            $classes = implode( ' ', $classes );
        }

        $link_html = str_replace( 'href', 'class="' . esc_attr( $classes ) . '" href', $link_html );
    }

    return $link_html;
}
