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
 * Adds support for custom post types and taxonomies to define
 * permalink structures with rewrite tags.
 *
 * When registering a post type or taxonomy you can add a value to the rewrite
 * property with the key 'permastruct' to define your default permalink structure.
 *
 * ```
 * register_post_type( 'bks-books', [
 *      'rewrite' => [ 'permastruct' => '/%bks-genre%/%author%/%postname%/' ],
 * ] );
 * ```
 *
 * Alternatively you can set the permalink structure from the permalinks settings page
 * in the WordPress administration area.
 *
 * @link https://github.com/interconnectit/wp-permastructure
 *       Based on Interconnectit's plugin.
 */
class Permastructures extends AbstractFeature
{
    /**
     * The feature key.
     *
     * @var string
     */
    const FEATURE_NAME = 'permastructure';

    /**
     * The option name of the feature.
     *
     * @var string
     */
    const OPTION_NAME = 'thelonius_permalink_structures';

    protected $settingsSection = 'permalink_structures';

    protected $endpoints;

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
        # add_action( 'init',        [ $this, 'protectEndpoints' ] );
        add_action( 'admin_init',  [ $this, 'registerSettings' ] );
    }

    /**
     * Register filters related to the post type.
     *
     * @return void
     */
    public function registerFilters()
    {
        add_filter( 'post_rewrite_rules', [ $this, 'addPermastructs' ] );
        # add_filter( 'post_link',          [ $this, 'parsePostLink'   ], 10, 3 );
        add_filter( 'post_type_link',     [ $this, 'parsePostLink'   ], 9, 4 );
    }

    /**
     * Protect rewrite endpoints from being overriden if a category/tag slug is set.
     *
     * @return void
     */
    public function protectEndpoints()
    {
        global $wp_rewrite;

        $this->endpoints = $wp_rewrite->endpoints;
    }

    /**
     * Filter rewrite rules used and removes unnecessary rules and add in the new rules.
     *
     * @param  array  $rules  The rewrite rules for posts.
     * @return array  The modified rules array.
     */
    public function addPermastructs( $rules )
    {
        global $wp_rewrite;

        // Restore endpoints
        /*
        if ( empty( $wp_rewrite->endpoints ) && ! empty( $this->endpoints ) ) {
            $wp_rewrite->endpoints = $this->endpoints;
        }
        */

        /** @todo Document the filters */
        $post_type_permastruct = apply_filters(
            "thelonius/permastructures/rewrite_rules/{$post_type}/permastruct",
            $wp_rewrite->permalink_structure,
            get_post_type_object( 'post' )
        );

        $post_type_permastruct = apply_filters(
            'thelonius/permastructures/rewrite_rules/post_type/permastruct',
            $post_type_permastruct,
            get_post_type_object( 'post' )
        );

        $permastructs = [
            $post_type_permastruct => [ 'post' ]
        ];

        /*
        $permastructs = [
            $wp_rewrite->permalink_structure => [ 'post' ]
        ];
        */

        // force page rewrite to bottom
        $wp_rewrite->use_verbose_page_rules = false;

        $post_types = $this->getPostTypes();
        $settings   = get_option( static::OPTION_NAME, [] );

        // get permastructs foreach custom post type and group any that use the same struct
        foreach ( $post_types as $post_type_obj ) {
            $post_type = $post_type_obj->name;
            if ( ! isset( $settings[$post_type] ) ) {
                $settings[$post_type] = null;
            }

            // add/override the custom permalink structure if set in options
            $post_type_permastruct = $settings[$post_type];
            if ( ! empty( $post_type_permastruct ) ) {
                if ( ! is_array( $post_type_obj->rewrite ) ) {
                    $post_type_obj->rewrite = [];
                }

                $post_type_obj->rewrite['permastruct'] = $post_type_permastruct;
            }

            // check we have a custom permalink structure
            if ( ! is_array( $post_type_obj->rewrite ) || ! isset( $post_type_obj->rewrite['permastruct'] ) ) {
                continue;
            }

            $post_type_permastruct = $post_type_obj->rewrite['permastruct'];

            /** @todo Document the filters */
            $post_type_permastruct = apply_filters(
                "thelonius/permastructures/rewrite_rules/{$post_type}/permastruct",
                $post_type_permastruct,
                $post_type_obj
            );

            $post_type_permastruct = apply_filters(
                'thelonius/permastructures/rewrite_rules/post_type/permastruct',
                $post_type_permastruct,
                $post_type_obj
            );

            if ( 'post' !== $post_type ) {
                // remove default struct rules
                add_filter( "{$post_type}_rewrite_rules", '__return_empty_array', 11 );
            }

            if ( ! isset( $permastructs[ $post_type_permastruct ] ) ) {
                $permastructs[ $post_type_permastruct ] = [];
            }

            $permastructs[ $post_type_permastruct ][] = $post_type;
        }

        $rules = [];

        /** @todo Document the filter */
        $permastructs = apply_filters( 'thelonius/permastructures/rewrite_rules/permastructs', $permastructs );

        error_log(__METHOD__);
        error_log(var_export($permastructs,true));

        // add our permastructs scoped to the post types - overwriting any keys that already exist
        foreach ( $permastructs as $struct => $post_types ) {
            $postnames   = $post_types;
            $postnames[] = 'postname';

            // if a struct is %postname% only then we need page rules first - if not found WP tries again with later rules
            if ( preg_match( '#^/?%(' . implode( '|', $postnames ) . ')%/?$#', $struct ) ) {
                $wp_rewrite->use_verbose_page_rules = true;
            }

            // get rewrite rules without walking dirs
            $post_type_rules_temp = $wp_rewrite->generate_rewrite_rules( $struct, EP_PERMALINK, false, true, false, false, true );
            foreach ( $post_type_rules_temp as $regex => $query ) {
                if ( preg_match( '/(&|\?)(cpage|attachment|p|name|pagename)=/', $query ) ) {
                    if ( count( $post_types ) < 2 ) {
                        $post_type_query = '&post_type=' . $post_types[0];
                    } else {
                        $post_type_query = '&post_type[]=' . join( '&post_type[]=', array_unique( $post_types ) );
                    }

                    $rules[ $regex ] = $query . ( preg_match( '/(&|\?)(attachment|pagename)=/', $query ) ? '' : $post_type_query );
                } else {
                    unset( $rules[ $regex ] );
                }
            }

        }

        return $rules;
    }

    /**
     * Parse the permalink rewrite tags for a post.
     *
     * @used-by Filter: 'post_link'
     * @used-by Filter: 'post_type_link'
     *
     * @uses   parse_post_permalink()
     * @uses   get_post_querylink()
     *
     * @param  string        $post_link  The post's permalink.
     * @param  mixed         $post       The post in question.
     * @param  boolean       $leavename  Optional. Whether to keep the post name.
     * @param  boolean|null  $sample     Optional. Is it a sample permalink.
     *
     * @return string|boolean  The permalink URL or false if post does not exist.
     */
    public function parsePostLink( $post_link, $post, $leavename = false, $sample = null )
    {
        if ( ! $post instanceof WP_Post ) {
            $post = get_post($post);
        }

        $has_changed   = false;
        $post_type     = $post->post_type;
        $post_type_obj = get_post_type_object( $post_type );
        $settings      = get_option( static::OPTION_NAME, [] );

        if ( ! empty( $settings[$post_type] ) ) {
            $has_changed = true;
            $post_link   = $settings[$post_type];
        } elseif ( ! empty( $post_type_obj->rewrite['permastruct'] ) ) {
            $has_changed = true;
            $post_link   = $post_type_obj->rewrite['permastruct'];
        }

        /**
         * Filter the list of unpublished post statuses by post type.
         *
         * @param array    $statuses  The statuses for which permalinks don't work.
         * @param WP_Post  $post      The post in question.
         */
        $unpublished_statuses = apply_filters(
            "thelonius/permastructures/{$post_type}/unpublished_statuses",
            get_unpublished_post_statuses(),
            $post
        );

        // If we aren't published, permalinks don't work
        $is_unpublished = ( isset( $post->post_status ) && in_array( $post->post_status, $unpublished_statuses ) );

        if ( ! empty( $post_link ) && ( ! $is_unpublished || $sample ) ) {
            $post_link = parse_post_permalink( $post_link, $post, $leavename );

            if ( $has_changed ) {
                $post_link = home_url( $post_link );
                $post_link = user_trailingslashit( $post_link, 'single' );
            }
        }

        return $post_link;
    }

    /**
     * Retrieve the "edit_permastruct_label" label from the given post type.
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

        if ( isset( $post_type->labels->edit_permastruct_label ) ) {
            return $post_type->labels->edit_permastruct_label;
        } else {
            return $post_type->label;
        }
    }

    /**
     * Register the section and settings to allow administrators to choose a static page
     * for post types that support this feature.
     *
     * @used-by Action: "load-{$page_hook}" (`options-permalink.php`)
     *     documented in wp-admin/admin.php
     *
     * @return void
     */
    public function registerSettings()
    {
        $post_types = $this->getPostTypes();

        if ( count( $post_types ) ) {
            register_setting(
                'permalink',
                static::OPTION_NAME,
                [ $this, 'sanitizeSettings' ]
            );

            add_settings_section(
                $this->settingsSection,
                sprintf( _x( '%s Options', 'page for posts', 'thelonius' ), get_bloginfo('name') ),
                [ $this, 'renderSettingsSection' ],
                'permalink'
            );

            $settings = get_option( static::OPTION_NAME, [] );

            foreach ( $post_types as $post_type_obj ) {
                $post_type = $post_type_obj->name;

                if ( ! isset( $settings[$post_type] ) ) {
                    $settings[$post_type] = null;
                }

                add_settings_field(
                    $post_type,
                    $this->getFieldLabelFrom( $post_type_obj ),
                    [ $this, 'renderSettingsField' ],
                    'permalink',
                    $this->settingsSection,
                    [
                        'post_type' => $post_type_obj,
                        'name'      => sprintf( '%1$s[%2$s]', static::OPTION_NAME, $post_type ),
                        'value'     => $settings[$post_type]
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
        foreach ( $value as $post_type => &$struct ) {
            $struct = $this->sanitizePermastruct( $struct, $post_type );
        }

        return $value;
    }

    /**
     * Sanitize a permalink structure for a given post type.
     *
     * @param  array   $value      The "permalink_structures" input value.
     * @param  string  $post_type  The name of the post type the structure belongs to.
     * @return array
     */
    public function sanitizePermastruct( $struct, $post_type = null )
    {
        /** @todo Document the filters */
        $required_tags = [ 'post_id', 'postname' ];

        if ( $post_type ) {
            if ( is_string( $post_type ) ) {
                $required_tags[] = $post_type;
            } elseif ( isset( $post_type->name ) ) {
                $required_tags[] = $post_type->name;
            }
        }

        $required_tags = apply_filters(
            'thelonius/permastructures/required_rewrite_tags',
            $required_tags,
            $struct,
            $post_type
        );

        $pattern = apply_filters(
            'thelonius/permastructures/required_rewrite_tags/regex_pattern',
            '/%(' . implode( '|', $required_tags ) . ')%/',
            $struct,
            $post_type
        );

        if ( ! empty( $struct ) && ! preg_match( $pattern, $struct ) ) {
            add_settings_error(
                static::OPTION_NAME,
                10,
                __( 'Permalink structures must contain at least the <code>%post_id%</code> or <code>%postname%</code>.' )
            );
        }

        $struct   = preg_replace( '#/+#', '/', '/' . str_replace( '#', '', $struct ) );
        $filtered = wp_check_invalid_utf8( $struct );

        if ( strpos($filtered, '<') !== false ) {
            $filtered = wp_pre_kses_less_than( $filtered );
            // This will strip extra whitespace for us.
            $filtered = wp_strip_all_tags( $filtered, true );
        } else {
            $filtered = trim( preg_replace('/[\r\n\t ]+/', ' ', $filtered) );
        }

        return preg_replace( '/[^a-zA-Z0-9\/\%_-]*/', '', $filtered );
    }

    /**
     * Render supplementary content related to the section.
     */
    public function renderSettingsSection()
    {
        echo '<p>' . __('If you like, you may enter custom structures for your extra content type <abbr title="Universal Resource Locator">URL</abbr>s here.', 'aqcpe') . ' ' . sprintf( __('For example, using a more common "%s" structure would make your article links like <code>http://example.org/editorials/%s/sample-post/</code>. If you leave these blank the defaults—the article name—will be used.', 'aqcpe'), '<strong>' . __('Month and name') . '</strong>', date('Y/m') ) . '</p>';
    }

    /**
     * Render the fields with the desired inputs as part of the Reading Settings page.
     *
     * @param array  $args  Optional. Extra arguments used when outputting the field.
     */
    public function renderSettingsField( $args )
    {
        global $wp_rewrite;

        if ( ! isset( $args['post_type'] ) ) {
            throw new InvalidArgumentException( 'The field must be assigned to a post type.' );
        }

        $name      = esc_attr( $args['name'] );
        $value     = esc_attr( $args['value'] );
        $post_type = $args['post_type']->name;
        $prefix    = '';

        if ( isset( $args['post_type']->rewrite['permastruct'] ) ) {
            $placeholder = $args['post_type']->rewrite['permastruct'];
        } else {
            $placeholder = "/%{$post_type}%";

            if ( isset( $args['post_type']->rewrite['slug'] ) ) {
                $prefix = $args['post_type']->rewrite['slug'];
            }
        }

        if ( isset( $args['post_type']->rewrite['with_front'] ) && $args['post_type']->rewrite['with_front'] ) {
            $prefix = substr( $wp_rewrite->front, 1 ) . $prefix;
        } else {
            $prefix = $wp_rewrite->root . $prefix;
        }

        if ( $value ) {
            /** @todo Document the filter */
            $value = apply_filters(
                "thelonius/permastructures/{$post_type}/output",
                $value
            );
        }

        if ( ! got_url_rewrite() ) {
            $prefix = "?post_type={$post_type}&p=123";
        }

        printf(
            '<code>%s</code>',
            home_url( $prefix )
        );

        if ( got_url_rewrite() ) {
            printf(
                '<input type="text" name="%1$s" id="%2$s" value="%3$s" placeholder="%4$s" class="regular-text code">',
                $name,
                sprintf( '%s_permalink_structure', $post_type ),
                $value,
                esc_attr( $placeholder )
            );
        }
    }

    /**
     * Retrieve the post type objects that support this feature.
     *
     * @return array
     */
    protected function getPostTypes()
    {
        $post_types = get_post_types( [ '_builtin' => false, 'public' => true ], 'objects' );

        if ( is_array( $this->options['ignored_post_types'] ) ) {
            $ignored = $this->options['ignored_post_types'];

            $post_types = array_filter( $post_types, function ( $post_type ) use ( $ignored ) {
                return ! in_array( $post_type->name, $ignored );
            } );
        }

        return $post_types;
    }
}
