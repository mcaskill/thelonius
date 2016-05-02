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

namespace Thelonius\PostType\Features;

use InvalidArgumentException;
use Thelonius\PostType\Features\AbstractFeature;

/**
 * Adds support for custom post types to use a static page
 * as their archive stand-in.
 *
 * You can enable/disable this feature with:
 *
 * ```
 * add_post_type_support( 'product', 'page-for-posts' );
 * ```
 *
 * @link https://github.com/interconnectit/wp-permastructure
 *       Based on Interconnectit's plugin.
 */
class PageForPosts extends AbstractFeature
{
    /**
     * The feature key.
     *
     * @var string
     */
    const FEATURE_NAME = 'page-for-posts';

    /**
     * The option name of the feature.
     *
     * @var string
     */
    const OPTION_NAME = 'page_for_%s';

    /**
     * Retrieve the default options for the feature.
     *
     * @return array
     */
    protected function defaultOptions()
    {
        return [
            'ignored_post_types' => [ 'post', 'page', 'attachment', 'revision', 'nav_menu_item' ]
        ];
    }

    /**
     * Register actions related to the post type.
     *
     * @return void
     */
    public function registerActions()
    {
        add_action( 'load-options-reading.php', [ $this, 'registerSettings' ] );
    }

    /**
     * Retrieve the "page_for_items_setting" key from the given post type.
     *
     * @param  string|object $post_type Name of the post type to retrieve from.
     * @return string
     */
    private function getOptionKeyFrom( $post_type )
    {
        if ( is_string( $post_type ) ) {
            $post_type = get_post_type_object( $post_type );
        }

        if ( ! is_object( $post_type ) ) {
            throw new InvalidArgumentException( 'The given post type must be an object.' );
        }

        if ( isset( $post_type->labels->page_for_items_setting ) ) {
            return $post_type->labels->page_for_items_setting;
        } else {
            if ( isset( $post_type->name ) ) {
                return sprintf( static::OPTION_NAME, $post_type->name );
            }

            throw new InvalidArgumentException( 'The given post type is invalid.' );
        }
    }

    /**
     * Retrieve the "page_for_items" label from the given post type.
     *
     * @param  string|object $post_type Name of the post type to retrieve from.
     * @return string
     */
    private function getOptionLabelFrom( $post_type )
    {
        if ( is_string( $post_type ) ) {
            $post_type = get_post_type_object( $post_type );
        }

        if ( ! is_object( $post_type ) ) {
            throw new InvalidArgumentException( 'The given post type must be an object.' );
        }

        if ( isset( $post_type->labels->page_for_items ) ) {
            return $post_type->labels->page_for_items;
        } else {
            if ( isset( $post_type->name ) ) {
                return sprintf( __( '%1$s: %2$s', 'snc' ), $post_type->label );
            }

            throw new InvalidArgumentException( 'The given post type is invalid.' );
        }
    }

    /**
     * Register the section and settings to allow administrators to choose a static page
     * for post types that support this feature.
     *
     * @used-by Action: "load-{$page_hook}" (`options-reading.php`)
     *     documented in wp-admin/admin.php
     *
     * @return void
     */
    public function registerSettings()
    {
        $post_types = $this->getPostTypes();

        if ( count( $post_types ) ) {
            add_settings_section(
                'page_for_posts',
                sprintf( _x( '%s Options', 'page for posts', 'thelonius' ), get_bloginfo('name') ),
                '',
                'reading'
            );

            array_walk($post_types, [ $this, 'registerSettingsField' ]);
        }
    }

    /**
     * Register the settings field for the given post type.
     *
     * @param  string|object $post_type Name of the post type to add a setting for.
     * @return void
     */
    public function registerSettingsField( $post_type )
    {
        if ( is_string( $post_type ) ) {
            $post_type = get_post_type_object( $post_type );
        }

        #$do_save = false;
        $option  = $this->getOptionKeyFrom( $post_type );
        #$post_ID = filter_input( INPUT_POST, $option, FILTER_SANITIZE_NUMBER_INT );

        #if ( $post_ID ) {
            /** @todo Document the filter */
            #$post_ID = apply_filters( "thelonius/page-for-posts/{$option}/input", $post_ID );
            #$do_save = true;
        #}

        register_setting( 'reading', $option, 'intval' );

        add_settings_field(
            $option,
            __( 'Static pages', 'snc' ),
            [ $this, 'renderSettingsField' ],
            'reading',
            'page_for_posts',
            [
                'post_type' => $post_type
            ]
        );

        #if ( $do_save ) {
            #update_option( $option, $post_ID );
        #}
    }

    /**
     * Render the fields with the desired inputs as part of the Reading Settings page.
     *
     * @param array  $args  Optional. Extra arguments used when outputting the field.
     */
    public function renderSettingsField( $args )
    {
        if ( ! isset( $args['post_type'] ) ) {
            throw new InvalidArgumentException( 'The field must be assigned to a post type.' );
        }

        if ( is_string( $args['post_type'] ) ) {
            $post_type = get_post_type_object( $args['post_type'] );
        } else {
            $post_type = $args['post_type'];
        }

        $option = $this->getOptionKeyFrom( $post_type );
        $label  = $this->getOptionLabelFrom( $post_type );
        $value  = get_option( $option );

        if ( $value ) {
            /** @todo Document the filter */
            $value = apply_filters( "thelonius/page-for-posts/{$option}/output", $value );
        } else {
            $value = '';
        }

        printf(
            $label,
            wp_dropdown_pages( [
                'echo'              => 0,
                'name'              => $option,
                'show_option_none'  => __( '&mdash; Select &mdash;' ),
                'option_none_value' => '0',
                'selected'          => $value
            ] )
        );
    }
}
