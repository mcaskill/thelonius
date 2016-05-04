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

/**
 * Register special page templates to redirect posts.
 *
 * @link https://gist.github.com/leoken/4395160
 *
 * @todo Replace proxy / noop hooks with a proper observer / subject pattern.
 * @todo Add filter to alter / extend the list of special page templates.
 * @todo Move each redirection resolver to a method executed by a filter.
 */
trait PageRedirectionTemplates
{
    /**
     * The special templates.
     *
     * @var array
     */
    protected $pageTemplates = [];

    /**
     * List of hooks that trigger the cache update of available page templates.
     *
     * @var array
     */
    protected $templateRegistrationHooks = [];

    /**
     * Boot the page redirection trait for a post type.
     *
     * @return void
     */
    public function bootPageRedirectionTemplates()
    {
        $this->pageTemplates = [
            'redirection-child'  => __( 'Redirect to first child', 'aqcpe' ),
            'redirection-parent' => __( 'Redirect to closest parent', 'aqcpe' )
        ];

        /**
         * A collection of hooks used to trigger the registration
         * of fake custom page templates.
         */
        $this->templateRegistrationHooks = [
            'substrate/page_template_column/column_added' => 'action',
            'page_attributes_dropdown_pages_args'         => 'filter',
            'quick_edit_dropdown_pages_args'              => 'filter',
            'wp_insert_post_data'                         => 'filter'
        ];

        foreach ( $this->templateRegistrationHooks as $hook => $type ) {
            call_user_func_array( "add_{$type}", [ $hook, [ &$this, "{$type}Noop" ], 1 ] );
        }

        add_filter( 'template_include',  [ &$this, 'templateInclude'  ], 1 );
        add_action( 'template_redirect', [ &$this, 'templateRedirect' ], 1 );
    }

    // Registration of Templates
    // =========================================================================

    /**
     * Proxy "noop" for the WordPress Action API.
     *
     * @used-by Action: 'substrate/page_template_column/column_added'
     *
     * @param  mixed  $args...  This function may be called on an action.
     * @return void|mixed
     */
    public function actionNoop()
    {
        $this->noop( 'action', current_action() );

        if ( func_num_args() ) {
            return func_get_arg(0);
        }
    }

    /**
     * Proxy "noop" for the WordPress Filter API.
     *
     * @used-by Filter: 'page_attributes_dropdown_pages_args'
     * @used-by Filter: 'quick_edit_dropdown_pages_args'
     * @used-by Filter: 'wp_insert_post_data'
     *
     * @param  mixed  $args...  This function may be called on a filter.
     * @return void|mixed
     */
    public function filterNoop()
    {
        $this->noop( 'filter', current_filter() );

        if ( func_num_args() ) {
            return func_get_arg(0);
        }
    }

    /**
     * A noop method to trigger the addition of custom templates. This method will
     * execute once when triggered via the WordPress Hooks API.
     *
     * @param  string  $type  Either an "action" or a "filter".
     * @param  string  $hook  The hook name.
     *
     * @return void
     */
    public function noop( $type = false, $hook = false )
    {
        if ( $type && $hook ) {
            foreach ( $this->templateRegistrationHooks as $_hook => $_type ) {
                call_user_func_array( "remove_{$_type}", [ $_hook, [ &$this, "{$_type}Noop" ], 1 ] );
            }
        }

        $this->registerTemplates();
    }

    /**
     * Adds fake custom templates to the pages cache in order to trick WordPress
     * into thinking the template file exists where it doens't really exist.
     *
     * @return void
     */
    public function registerTemplates()
    {
        // Create the key used for the themes cache
        $cache_key = 'page_templates-' . md5( get_theme_root() . '/' . get_stylesheet() );

        // Retrieve the cache list.
        // If it doesn't exist, or it's empty prepare an array
        $templates = wp_get_theme()->get_page_templates();
        if ( empty( $templates ) ) {
            $templates = [];
        }

        // New cache, therefore remove the old one
        wp_cache_delete( $cache_key , 'themes' );

        // Now add our template to the list of templates by merging our templates
        // with the existing templates array from the cache.
        $templates = array_merge( $templates, $this->pageTemplates );

        // Add the modified cache to allow WordPress to pick it up for listing
        // available templates
        wp_cache_add( $cache_key, $templates, 'themes', 1800 );
    }



// Parsing of Templates
// ==========================================================================

    /**
     * Filter the path of the current template before including it,
     * executing any template swaps.
     *
     * @used-by Filter: 'template_include'
     *
     * @param  string  $template  The path of the template to include.
     * @return string
     */
    public function templateInclude( $template )
    {
        if ( ! is_admin() && is_home() ) {
            global $post;

            $file = basename( $template );

            if ( 'home.php' !== $file && locate_template( 'archive.php' ) ) {
                $template = preg_replace( '#' . $file . '$#', 'archive.php', $template );
            }
        }

        return $template;
    }

    /**
     * Fires before determining which template to load,
     * executing any custom template redirections.
     *
     * @used-by Action: 'template_redirect'
     *
     * @return void
     */
    public function templateRedirect()
    {
        if ( ! is_admin() && is_page() ) {
            global $post;

            $post->template = get_post_meta( $post->ID, '_wp_page_template', true );

            if ( isset( $this->pageTemplates[ $post->template ] ) ) {
                if ( 'redirection-child' === $post->template ) {
                    $destination = get_children( [
                        'numberposts' => 1,
                        'post_parent' => $post->ID,
                        'post_type'   => 'page',
                        'post_status' => 'publish',
                        'orderby'     => 'menu_order',
                        'order'       => 'ASC'
                    ] );
                }

                if ( 'redirection-parent' === $post->template && $post->post_parent > 0 ) {
                    $destination = get_post( $post->post_parent );
                }

                if (
                    1 == count( $destination ) &&
                    wp_redirect( get_permalink( current( $destination )->ID ), 303 )
                ) {
                    exit;
                }
            }
        }
    }
}
