<?php

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Support\Debug\Dumper;
use Illuminate\Contracts\Support\Htmlable;

use Thelonius\Application;
use Thelonius\Container\Container;
use Thelonius\PostType\PostTypeInterface;

if ( ! function_exists('app') ) {
    /**
     * Get the available container instance.
     *
     * @link https://github.com/laravel/framework/blob/5.2/src/Illuminate/Foundation/helpers.php
     *
     * @param  string  $app  A Thelonius application instance.
     * @return mixed|Application
     */
    function app($app = null)
    {
        if (is_null($app)) {
            return Container::getInstance();
        }

        return Container::setInstance($app);
    }
}

if ( ! function_exists('env') ) {
    /**
     * Gets the value of an environment variable. Supports boolean, empty and null.
     *
     * @link https://github.com/laravel/framework/blob/5.2/src/Illuminate/Foundation/helpers.php
     *
     * @param string  $key      The environment variable name.
     * @param mixed   $default  The default value to return if no value is returned.
     *
     * @return mixed
     */
    function env($key, $default = null)
    {
        $value = getenv($key);

        if ($value === false) {
            return value($default);
        }

        switch (strtolower($value)) {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'empty':
            case '(empty)':
                return '';
            case 'null':
            case '(null)':
                return;
        }
        if (strlen($value) > 1 && Str::startsWith($value, '"') && Str::endsWith($value, '"')) {
            return substr($value, 1, -1);
        }
        return $value;
    }
}

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
