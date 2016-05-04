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
 *
 * @todo Add support for wp-admin/edit-form-advanced.php:53
 * @todo Add support for wp-admin/includes/meta-boxes.php:820
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
    const OPTION_NAME = 'thelonius_page_for_posts';

    protected $settingsSection = 'page_for_posts';

    protected $pagesForPosts;

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
        $option = static::OPTION_NAME;

        add_action( 'admin_init',  [ $this, 'registerSettings' ] );
        add_action( 'parse_query', [ $this, 'parseQuery'       ] );

        /**
         * Fires after the value of a specific option has been successfully updated.
         */
        add_action( "update_option_{$option}", 'flush_rewrite_rules' );

        add_action( 'post_updated', [ $this, 'checkForChangedSlugs' ], 12, 3 );
    }

    /**
     * Register filters related to the post type.
     *
     * @return void
     */
    public function registerFilters()
    {
        add_filter( 'post_type_labels_page', [ $this, 'postTypeLabels'    ] );
        add_filter( 'display_post_states',   [ $this, 'displayPostStates' ], 10, 2 );

        add_filter(
            'thelonius/permastructures/rewrite_rules/post_type/permastruct',
            [ $this, 'parsePostTypePermastruct' ],
            10,
            2
        );

        add_filter( 'post_link',      [ $this, 'parsePostTypePermastruct' ], 10, 2 );
        add_filter( 'post_type_link', [ $this, 'parsePostTypePermastruct' ], 10, 2 );
    }

    /**
     * Fires once an existing post has been updated.
     *
     * @used-by Action: 'post_updated'
     *
     * @see wp_check_for_changed_slugs()
     *
     * @param int     $post_ID      Post ID.
     * @param WP_Post $post_after   Post object following the update.
     * @param WP_Post $post_before  Post object before the update.
     */
    public function checkForChangedSlugs( $post_ID, $post_after, $post_before )
    {
        $settings = get_option( static::OPTION_NAME, [] );

        if ( ! array_search( $post_ID, $settings ) ) {
            return;
        }

        if (
            $post_after->post_name   === $post_before->post_name &&
            $post_after->post_parent === $post_before->post_parent
        ) {
            return;
        }

        flush_rewrite_rules();
    }

    /**
     * Fires after the main query vars have been parsed.
     *
     * @used-by Action: "parse_query" documented in wp-includes/query.php
     *
     * @param  WP_Query  &$query  The WP_Query instance (passed by reference).
     * @return void
     */
    public function parseQuery( &$query )
    {
        if ( $query->is_home ) {
            return;
        }

        $query_vars_changed = false;

        $qv = &$query->query_vars;

        $settings = get_option( static::OPTION_NAME, [] );

        if ( '' != $qv['pagename'] && isset( $query->queried_object_id ) ) {
            if ( $post_type = array_search( $query->queried_object_id, $settings ) ) {
                $this->enablePostsPage( $query );

                $qv['post_type'] = $post_type;

                $query_vars_changed = true;
            }
        }

        if ( $qv['page_id'] ) {
            if ( $post_type = array_search( $qv['page_id'], $settings ) ) {
                $this->enablePostsPage( $query );

                $qv['post_type'] = $post_type;

                $query_vars_changed = true;
            }
        }

        if ( $query_vars_changed ) {
            if ( $query->is_posts_page && ( ! isset($qv['withcomments']) || ! $qv['withcomments'] ) ) {
                $query->is_comment_feed = false;
            }

            if ( ! $query->is_page && $query->is_post_type_archive ) {
                unset( $qv['page_id'], $qv['pagename'] );
            }
        }
    }

    /**
     * Declare the queried object as a page for posts.
     *
     * @used-by self::parseQuery()
     *
     * @param  WP_Query  &$query  The WP_Query instance (passed by reference).
     * @return void
     */
    private function enablePostsPage( &$query )
    {
        $query->is_single            = false;
        $query->is_singular          = false;
        $query->is_page              = false;
        $query->is_home              = false;
        $query->is_archive           = true; // false ?
        $query->is_post_type_archive = true; // false ?
        $query->is_posts_page        = false; // true ?
    }

    /**
     * Filter the labels of a specific post type.
     *
     * The dynamic portion of the hook name, `$post_type`, refers to
     * the post type slug.
     *
     * @used-by Filter: "post_type_labels_{$post_type}" documented in wp-includes/post.php
     *
     * @see get_post_type_labels() for the full list of labels.
     *
     * @param  object  $labels  Object with labels for the post type as member variables.
     * @return object
     */
    public function postTypeLabels( $labels )
    {
        if ( ! isset( $labels->page_for_items ) ) {
            $labels->page_for_items = sprintf(
                _x( '%s Page', 'Page for post type', 'thelonius' ),
                $labels->name
            );
        }

        if ( ! isset( $labels->page_for_items_field ) ) {
            $labels->page_for_items_field = sprintf(
                _x( '%1$s page: %2$s', '1. Post type; 2. "%s"', 'thelonius' ),
                $labels->name,
                '%s'
            );
        }

        return $labels;
    }

    /**
     * Filter the default post display states used in the posts list table.
     *
     * @used-by Filter: 'display_post_states' documented in wp-admin/includes/template.php
     *
     * @param  array   $post_states An array of post display states.
     * @param  WP_Post $post        The current post object.
     * @return array
     */
    public function displayPostStates( $post_states, $post )
    {
        $settings = get_option( static::OPTION_NAME, [] );

        if ( $post_type = array_search( $post->ID, $settings ) ) {
            $post_type_obj  = get_post_type_object( $post_type );
            $page_for_posts = sprintf( 'page_for_%s', $post_type );

            $post_states[$page_for_posts] = $post_type_obj->labels->page_for_items;
        }

        return $post_states;
    }

    /**
     * Filter the permalink structure used for a post type permastruct.
     *
     * @used-by Filter: 'thelonius/permastructures/rewrite_rules/permastruct'
     *     documented in {@see Thelonius\PostType\Features\Permastructures::addPermastructs()}
     *
     * @param  array   $permastruct  The post type permastruct.
     * @param  object  $object_type  The post type object.
     * @return array
     *
     * @todo Cache the rewrite tags built from the options.
     */
    public function parsePostTypePermastruct( $permastruct, $object_type )
    {
        if ( is_int( $object_type ) ) {
            $object_type = get_post_type( $object_type );
        } elseif ( isset( $object_type->post_type ) ) {
            $object_type = $object_type->post_type;
        } elseif ( isset( $object_type->name ) ) {
            $object_type = $object_type->name;
        }

        if ( isset( $this->pagesForPosts ) ) {
            $codes = $this->pagesForPosts;
        } else {
            $settings = get_option( static::OPTION_NAME, [] );

            if ( 'page' === get_option( 'show_on_front' ) && $page_ID = get_option( 'page_for_posts' ) ) {
                $settings['post'] = $page_ID;
            }

            foreach ( $settings as $post_type => $page_ID ) {
                $codes["%page_for_{$post_type}%"] = get_page_uri( $page_ID );
            }

            $this->pagesForPosts = $codes;
        }

        if ( $codes ) {
            $codes['%page_for_posts%'] = $codes["%page_for_{$object_type}%"];

            $permastruct = str_replace(
                array_keys( $codes ),
                $codes,
                $permastruct
            );
        }

        return $permastruct;
    }

    /**
     * Retrieve the "page_for_items_field" label from the given post type.
     *
     * @param  string|object $post_type Name of the post type to retrieve from.
     * @return string
     */
    private function getFieldLabelFrom( $post_type )
    {
        if ( is_string( $post_type ) ) {
            $post_type = get_post_type_object( $post_type );
        }

        if ( ! is_object( $post_type ) ) {
            throw new InvalidArgumentException( 'The given post type must be an object.' );
        }

        if ( isset( $post_type->labels->page_for_items_field ) ) {
            return $post_type->labels->page_for_items_field;
        } else {
            return sprintf(
                _x( '%1$s: %2$s', 'Used to explain or start an enumeration', 'thelonius' ),
                $post_type->label,
                '%s'
            );
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
            register_setting(
                'reading',
                static::OPTION_NAME,
                [ $this, 'sanitizeSettings' ]
            );

            add_settings_section(
                $this->settingsSection,
                sprintf( _x( '%s Options', 'page for posts', 'thelonius' ), get_bloginfo('name') ),
                '',
                'reading'
            );

            $settings = get_option( static::OPTION_NAME, [] );

            foreach ( $post_types as $post_type ) {
                if ( ! isset( $settings[$post_type->name] ) ) {
                    $settings[$post_type->name] = null;
                }

                add_settings_field(
                    $post_type->name,
                    __( 'Static pages', 'snc' ),
                    [ $this, 'renderSettingsField' ],
                    'reading',
                    $this->settingsSection,
                    [
                        'post_type' => $post_type,
                        'field'     => $this->getFieldLabelFrom( $post_type ),
                        'name'      => sprintf( '%1$s[%2$s]', static::OPTION_NAME, $post_type->name ),
                        'value'     => $settings[$post_type->name]
                    ]
                );
            }
        }
    }

    /**
     * Sanitize the settings fields.
     *
     * @param  array  $value  The "permalink_structures" input value.
     * @return array
     */
    public function sanitizeSettings( $value )
    {
        foreach ( $value as $post_type => &$page_ID ) {
            /** @todo Document the filter */
            $page_ID = apply_filters(
                "thelonius/page-for-posts/{$post_type}/input",
                absint( $page_ID )
            );
        }

        return $value;
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

        $name      = esc_attr( $args['name'] );
        $value     = esc_attr( $args['value'] );
        $post_type = $args['post_type']->name;

        if ( $value ) {
            /** @todo Document the filter */
            $value = apply_filters(
                "thelonius/page-for-posts/{$post_type}/output",
                $value
            );
        }

        printf(
            $args['field'],
            wp_dropdown_pages( [
                'echo'              => 0,
                'name'              => $name,
                'id'                => sprintf( 'page_for_%s', $post_type ),
                'show_option_none'  => __( '&mdash; Select &mdash;' ),
                'option_none_value' => '0',
                'selected'          => $value
            ] )
        );
    }
}
