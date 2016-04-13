<?php

use WP_Error;

use Thelonius\PostType\PostTypeInterface;

if ( ! function_exists('wp_insert_post_object') ) {
    /**
     * Insert or update a post.
     *
     * @uses wp_insert_post()
     *
     * @param PostTypeInterface  $post      A post object to update or insert.
     * @param boolean            $wp_error  Optional. Whether to allow return of WP_Error on failure. Default false.
     *
     * @return integer|WP_Error  The post ID on success. The value 0 or WP_Error on failure.
     *
     * @todo Move this to a `PostManager::insert()` class / method.
     */
    function wp_insert_post_object(PostTypeInterface $post, $wp_error = false)
    {
        $post_id = wp_insert_post($post->getPostData(), $wp_error);

        if ( 0 === $post_id || $post_id instanceof WP_Error ) {
            return $post_id;
        }

        foreach ( $post->getPostMeta() as $key => $value ) {
            update_post_meta($post_id, $key, $value);
        }

        return $post_id;
    }
}
