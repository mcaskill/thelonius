<?php

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Support\Debug\Dumper;
use Illuminate\Contracts\Support\Htmlable;

use Thelonius\Application;
use Thelonius\Container\Container;
use Thelonius\Contracts\PostType\PostType as PostTypeInterface;
use Thelonius\PostType\PostTypeNotFoundException;

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

if ( ! function_exists('is_edit_page') ) {
    /**
     * When the post edit page is being displayed.
     *
     * @author Ohad Raz <admin@bainternet.info>
     * @link http://wordpress.stackexchange.com/a/50045/18350
     *
     * @todo Add filters on the arrays to match.
     *
     * @param  string  $new_edit  What page to check for accepts new - new post page, edit - edit post page, null for either
     * @return boolean
     */
    function is_edit_page( $new_edit = null )
    {
        global $pagenow;

        if ( ! is_admin() ) {
            return false;
        } elseif ( $new_edit === 'edit' ) {
            return in_array( $pagenow, [ 'post.php' ] );
        } elseif ( $new_edit === 'new' ) {
            /** Check for new post page */
            return in_array( $pagenow, [ 'post-new.php' ] );
        } else {
            /** Check for either new or edit */
            return in_array( $pagenow, [ 'post.php', 'post-new.php' ] );
        }
    }
}

if ( ! function_exists('add_menu_separator') ) {
    /**
     * Add a top level separator.
     *
     * If you're running into PHP errors such as "Invalid argument supplied
     * for `foreach()`" or the "You do not have sufficient permissions to
     * access this page" error, then you've hooked too early. The action hook
     * you should use is {@see `admin_menu`}.
     *
     * @global $menu The WordPress administration menu.
     *
     * @param int $position The position in the menu order this one should appear.
     */
    function add_menu_separator( $position )
    {
        global $menu;

        $index = 0;

        foreach ( $menu as $offset => $section ) {
            if ( substr( $section[2], 0, 9 ) === 'separator' ) {
                $index++;
            }

            if ( $offset >= $position ) {
                $menu[ $position ] = [ '', 'read', "separator{$index}", '', 'wp-menu-separator' ];
                break;
            }
        }

        ksort( $menu );
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

if ( ! function_exists('has_query_var') ) {
    /**
     * Check if the current variable is set, and is not NULL, in the WP_Query class.
     *
     * @global WP_Query $wp_query Global WP_Query instance.
     *
     * @param string    $var    The variable key to check for.
     * @param WP_Query  $query  A WP_Query instance.
     *
     * @return boolean  True if the current variable is set.
     */
    function has_query_var( $var, WP_Query $query = null )
    {
        global $wp_query;

        if ( ! isset( $query ) ) {
            $query = $wp_query;
        }

        return isset( $query->query_vars[ $var ] );
    }
}

if ( ! function_exists('get_query_vars') ) {
    /**
     * Retrieve variables in the WP_Query class.
     *
     * @param  mixed[]   $vars   A collection of variables to retrieve.
     * @param  WP_Query  $query  A WP_Query instance.
     *
     * @return mixed[]
     */
    function get_query_vars( array $vars, WP_Query $query = null )
    {
        $vals = [];

        if ( Arr::isAssoc( $vars ) ) {
            foreach ( $vars as $var => $default ) {
                if ( $query instanceof WP_Query ) {
                    $val = $query->get( $var, $default );
                } else {
                    $val = get_query_var( $var, $default );
                }

                $vals[ $var ] = $val;
            }
        } else {
            foreach ( $vars as $var ) {
                if ( $query instanceof WP_Query ) {
                    $vals[ $var ] = $query->get( $var, null );
                } else {
                    $vals[ $var ] = get_query_var( $var, null );
                }
            }
        }
        return $vals;
    }
}

if ( ! function_exists('set_queried_object') ) {
    /**
     * Set the currently-queried object.
     *
     * @global WP_Query $wp_query Global WP_Query instance.
     *
     * @param  object    $object  Queried object.
     * @param  WP_Query  &$query  A WP_Query instance (passed by reference).
     */
    function set_queried_object( $object, WP_Query &$query = null )
    {
        global $wp_query;

        if ( ! isset( $query ) ) {
            $query = &$wp_query;
        }

        $query->queried_object    = $object;
        $query->queried_object_id = ( isset( $object->ID ) ? (int) $object->ID : 0 );
    }
}

if ( ! function_exists('parse_post_link') ) {
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
    function parse_post_link($post_link, $post, $leavename = false, $sample = null)
    {
        global $wp_rewrite;

        if ( ! $post instanceof WP_Post ) {
            $post = get_post($post);
        }

        $post_type = $post->post_type;

        if ( ! post_type_exists( $post_type ) ) {
            throw new PostTypeNotFoundException($post);
        }

        $sample = ( null === $sample && isset($post->filter) && ('sample' === $post->filter) );

        $post_type_obj = get_post_type_object( $post_type );
        $permastruct   = get_option( "{$post_type}_permalink_structure" );
        $has_changed   = false;

        // Prefer custom option over default
        if ( $permastruct && ! empty( $permastruct ) ) {
            $has_changed = true;
            $post_link   = $permastruct;
        } elseif ( ! empty( $post_type_obj->rewrite['permastruct'] ) ) {
            $has_changed = true;
            $post_link   = $post_type_obj->rewrite['permastruct'];
        }

        /** This filter is documented by {@see get_permalink()} in wp-includes/link-template.php */
        $post_link = apply_filters('pre_post_link', $post_link, $post, $leavename);

        /**
         * Filter the list of unpublished post statuses by post type.
         *
         * @param array    $statuses  The statuses for which permalinks don't work.
         * @param WP_Post  $post      The post in question.
         */
        $unpublished_statuses = apply_filters(
            "parse_{$post_type}_link/unpublished_statuses",
            get_unpublished_post_statuses(),
            $post
        );

        // If we aren't published, permalinks don't work
        $is_unpublished = ( isset( $post->post_status ) && in_array( $post->post_status, $unpublished_statuses ) );

        if ( ! empty( $post_link ) && ( ! $is_unpublished || $sample ) ) {
            $post_link = parse_post_permalink($post_link, $post, $leavename);

            if ( $has_changed ) {
                $post_link = home_url( $post_link );
                $post_link = user_trailingslashit( $post_link, 'single' );
            }
        } else {
            $post_link = get_post_querylink($post);
        }

        return $post_link;
    }
}

if ( ! function_exists('get_unpublished_post_statuses') ) {
    /**
     * Retrieve all of the non-public WordPress supported post statuses.
     *
     * Posts that are "unpublished" might behave differently, such as how permalinks are parsed.
     *
     * @return array List of unpublished post statuses.
     */
    function get_unpublished_post_statuses() {
        $status = [ 'draft', 'pending', 'auto-draft'/*, 'future' */ ];

        /**
         * Filter the unpublished post statuses.
         *
         * @param array  $status  List of non-public post statuses.
         */
        return apply_filters( 'unpublished_post_statuses', $status );
    }
}

if ( ! function_exists('get_post_querylink') ) {
    /**
     * Retrieve the "ugly" permalink for a post (query-based URL).
     *
     * @param  integer|WP_Post  $post  Optional. Post ID or post object. Default is the global `$post`.
     *
     * @return string|WP_Error The post URL.
     */
    function get_post_querylink($post = 0)
    {
        if ( ! $post instanceof WP_Post ) {
            $post = get_post($post);
        }

        if ( is_wp_error( $post ) ) {
            return $post;
        }

        $post_type     = $post->post_type;
        $post_type_obj = get_post_type_object($post_type);

        /** This filter is documented in {@see parse_post_link()} */
        $unpublished_statuses = apply_filters(
            "parse_{$post_type}_link/unpublished_statuses",
            get_unpublished_post_statuses(),
            $post
        );

        $has_post_status = isset( $post->post_status );
        $is_unpublished  = ( $has_post_status && in_array( $post->post_status, $unpublished_statuses ) );

        if ( $post_type_obj->query_var && ( $has_post_status && ! $is_unpublished ) ) {
            $post_link = add_query_arg( $post_type_obj->query_var, $slug, '' );
        } else {
            $post_link = add_query_arg( [ 'post_type' => $post_type, 'p' => $post->ID ], '' );
        }

        $post_link = home_url($post_link);

        /**
         * Filter the query URL for a post.
         *
         * @param string   $post_link  The post's URL.
         * @param WP_Post  $post       The post in question.
         */
        return apply_filters( 'post_query_link', $post_link, $post );
    }
}

if ( ! function_exists('get_term_parents') ) {
    /**
     * Retrieve term parents with separator.
     *
     * @link https://github.com/interconnectit/wp-permastructure
     *
     * @param  integer  $id         Term ID.
     * @param  string   $taxonomy   The taxonomy the term belongs to.
     * @param  boolean  $link       Optional. Whether to format with link. Default is FALSE.
     * @param  string   $separator  Optional. How to separate categories. Default is '/'.
     * @param  boolean  $nicename   Optional. Whether to use nice name for display. Default is FALSE.
     * @param  array    $visited    Optional. Already linked to categories to prevent duplicates.
     *
     * @return string
     */
    function get_term_parents( $id, $taxonomy, $link = false, $separator = '/', $nicename = false, $visited = [] )
    {
        $chain = '';
        $parent = get_term( $id, $taxonomy );

        if ( is_wp_error( $parent ) ) {
            return $parent;
        }

        if ( $nicename ) {
            $name = $parent->slug;
        } else {
            $name = $parent->cat_name;
        }

        if ( $parent->parent && ( $parent->parent != $parent->term_id ) && !in_array( $parent->parent, $visited ) ) {
            $visited[] = $parent->parent;
            $chain .= get_term_parents( $parent->parent, $taxonomy, $link, $separator, $nicename, $visited );
        }

        if ( $link ) {
            $chain .= '<a href="' . get_term_link( $parent->term_id, $taxonomy ) . '" title="' . esc_attr( sprintf( __( "View all posts in %s" ), $parent->name ) ) . '">'.$name.'</a>' . $separator;
        } else {
            $chain .= $name.$separator;
        }

        return $chain;
    }
}

if ( ! function_exists('parse_post_permalink') ) {
    /**
     * Parse permalink rewrite tags for a post.
     *
     * Ignores "draft or pending" post status. Internal use only.
     *
     * Replicates the operations performed in {@see get_permalink()}
     * to build a valid URL from a permastruct for custom taxonomies and post types.
     *
     * @link https://github.com/interconnectit/wp-permastructure
     *     Implements similar features from `wp_permastructure::parse_permalinks()`
     *
     * @used-by Filter: "wp\post_link"       Only applies to posts with post type of 'post'.
     * @used-by Filter: "wp\post_type_link"  Applies to posts with a custom post type.
     *
     * @global WP_Rewrite $wp_rewrite
     *
     * @global Polylang     $polylang   If Polylang is available, translations will be considered.
     *
     * @param  string   $post_link  The post's permalink.
     * @param  WP_Post  $post       The post in question.
     * @param  boolean  $leavename  Whether to keep the post name.
     *
     * @return string|boolean  The permalink URL or false if post does not exist.
     */
    function parse_post_permalink($post_link, WP_Post $post, $leavename = false)
    {
        global $wp_rewrite;

        if ( empty( $post_link ) ) {
            throw new InvalidArgumentException('Post link empty for post ID #%s.', $post->ID);
        }

        $post_type = $post->post_type;

        if ( ! post_type_exists( $post_type ) ) {
            throw new PostTypeNotFoundException($post);
        }

        $post_type_obj = get_post_type_object( $post_type );

        $slug = $post->post_name;

        if ( $post_type_obj->hierarchical ) {
            $slug = get_page_uri( $post );
        }

        /**
         * Filter the post name to use as a slug.
         *
         * @param string   $post_name  The post's name.
         * @param WP_Post  $post       The post in question.
         * @param boolean  $leavename  Whether to keep the post name.
         */
        $slug = apply_filters( "parse_post_permalink/slug", $slug, $post, $leavename );

        /**
         * Filter the post name to use as a slug for a specific post type.
         *
         * @param string   $post_name  The post's name.
         * @param WP_Post  $post       The post in question.
         * @param boolean  $leavename  Whether to keep the post name.
         */
        $slug = apply_filters( "parse_post_permalink_for_{$post_type}/slug", $slug, $post, $leavename );

        if ( ! $leavename ) {
            $post_link = str_replace( "%{$post_type}%", $slug, $post_link );
        }

        $rewrite_tags = [
            '%post_id%' => $post->ID
        ];

        if ( ! $leavename ) {
            $rewrite_tags['%postname%'] = $rewrite_tags['%pagename%'] = $slug;
        }

        /**
         * Filter post rewrite tags.
         *
         * @param array   $tags       The rewrite tags.
         * @param string  $post_link  A post permalink to resolve from.
         */
        $rewrite_tags = apply_filters( 'rewrite_tags/post', $rewrite_tags, $post_link );

        /**
         * Filter post rewrite tags for a specific post type.
         *
         * @param array    $tags       The rewrite tags.
         * @param string   $post_link  A post permalink to resolve from.
         * @param WP_Post  $post       The post to resolve from.
         */
        $rewrite_tags = apply_filters( "rewrite_tags_for_{$post_type}/post", $rewrite_tags, $post_link, $post );

        $rewrite_tags = array_replace(
            $rewrite_tags,
            get_date_rewrite_tags( $post, $post_link ),
            get_author_rewrite_tags( $post, $post_link ),
            get_taxonomy_rewrite_tags( $post, $post_link )
        );

        /**
         * Filter post permalink rewrite tags.
         *
         * @param array    $tags       The rewrite tags for post permalink structures.
         * @param string   $post_link  A post permalink to resolve from.
         * @param WP_Post  $post       The post in question.
         * @param boolean  $leavename  Whether to keep the post name.
         */
        $rewrite_tags = apply_filters(
            "parse_post_permalink/rewrite_tags",
            $rewrite_tags,
            $post_link,
            $post,
            $leavename
        );

        /**
         * Filter post permalink rewrite tags for a specific post type.
         *
         * @param array    $tags       The rewrite tags for post permalink structures.
         * @param string   $post_link  A post permalink to resolve from.
         * @param WP_Post  $post       The post in question.
         * @param boolean  $leavename  Whether to keep the post name.
         */
        $rewrite_tags = apply_filters(
            "parse_post_permalink_for_{$post_type}/rewrite_tags",
            $rewrite_tags,
            $post_link,
            $post,
            $leavename
        );

        $post_link = str_replace( array_keys($rewrite_tags), array_values($rewrite_tags), $post_link );

        return $post_link;
    }
}

/**
 * Retrieve author rewrite tags that can be used in permalink structures.
 *
 * @param  mixed   $author     A user nicename, WP_User, or WP_Post object.
 * @param  string  $post_link  A post permalink to resolve from.
 * @return array   Author rewrite codes and their replacements.
 */
function get_author_rewrite_tags( $author = null, $post_link = '' )
{
    $post = null;

    if ( strpos( $post_link, '%author%' ) !== false ) {
        if ( null === $author ) {
            $author = '';
        } elseif ( $author instanceof WP_Post ) {
            $post = $author;
            $authordata = get_userdata( $post->post_author );
            $author = $authordata->user_nicename;
        } elseif ( $author instanceof WP_User ) {
            $author = $author->user_nicename;
        } elseif ( is_int($author) ) {
            $author = get_user_by( 'ID', $author );
        }
    } else {
        $author = '';
    }

    $tags = [
        '%author%' => $author
    ];

    /**
     * Filter author rewrite tags.
     *
     * @param array   $tags       The rewrite tags for author permalink structures.
     * @param string  $post_link  A post permalink to resolve from.
     */
    $tags = apply_filters( 'rewrite_tags/author', $tags, $post_link );

    if ( $post instanceof WP_Post ) {
        $post_type = $post->post_type;

        /**
         * Filter author rewrite tags for a specific post type.
         *
         * @param array    $tags       The rewrite tags.
         * @param string   $post_link  A post permalink to resolve from.
         * @param WP_Post  $post       The post to resolve from.
         */
        $tags = apply_filters( "rewrite_tags_for_{$post_type}/author", $tags, $post_link, $post );
    }

    return $tags;
}

/**
 * Retrieve date/time rewrite tags that can be used in permalink structures.
 *
 * @param  mixed   $time       A date/time string or WP_Post object.
 * @param  string  $post_link  A post permalink to resolve from.
 * @return array   Date-based rewrite codes and their replacements.
 */
function get_date_rewrite_tags( $time = null, $post_link = '' )
{
    $post = null;

    if ( null === $time ) {
        $time = time();
    } elseif ( $time instanceof WP_Post ) {
        $post = $time;
        $time = strtotime( $post->post_date );
    } else {
        $time = strtotime( $time );
    }

    $date = explode( ' ', date( 'Y m d H i s', $time ) );
    $tags = [
        '%year%'     => $date[0],
        '%monthnum%' => $date[1],
        '%day%'      => $date[2],
        '%hour%'     => $date[3],
        '%minute%'   => $date[4],
        '%second%'   => $date[5]
    ];

    /**
     * Filter date/time rewrite tags.
     *
     * @param array   $tags       The rewrite tags.
     * @param string  $post_link  A post permalink to resolve from.
     */
    $tags = apply_filters( 'rewrite_tags/date', $tags, $post_link );

    if ( $post instanceof WP_Post ) {
        $post_type = $post->post_type;

        /**
         * Filter date/time rewrite tags for a specific post type.
         *
         * @param array    $tags       The rewrite tags.
         * @param string   $post_link  A post permalink to resolve from.
         * @param WP_Post  $post       The post to resolve from.
         */
        $tags = apply_filters( "rewrite_tags_for_{$post_type}/date", $tags, $post_link, $post );
    }

    return $tags;
}

/**
 * Retrieve taxonomy rewrite tags that can be used in permalink structures.
 *
 * @param  mixed   $taxonomies  An array of taxonomy names or WP_Post object.
 * @param  string  $post_link   A post permalink to resolve from.
 * @return array   Taxonomy rewrite codes and their replacements.
 */
function get_taxonomy_rewrite_tags( $taxonomies = null, $post_link = '' )
{
    $post = null;

    if ( null === $taxonomies ) {
        $taxonomies = get_taxonomies();
    } elseif ( $taxonomies instanceof WP_Post ) {
        $post = $taxonomies;
        $taxonomies = get_object_taxonomies( $post->post_type );
    } elseif ( ! is_array( $taxonomies ) ) {
        $taxonomies = [];
    }

    $tags = [];
    foreach ( $taxonomies as $taxonomy ) {
        $tax  = "%{$taxonomy}%";
        $term = '';

        $taxonomy_object = get_taxonomy( $taxonomy );
        if ( strpos($post_link, $tax) !== false ) {
            $terms = get_the_terms( $post->ID, $taxonomy );

            if ( $terms ) {
                usort($terms, '_usort_terms_by_ID');
                $term = $terms[0]->slug;
                if ( $taxonomy_object->hierarchical && $parent = $terms[0]->parent ) {
                    $term = get_term_parents($parent, $taxonomy, false, '/', true) . $term;
                }
            }

            /** Show default category in permalinks, without having to assign it explicitly */
            if ( empty( $term ) && $taxonomy === 'category' ) {
                $default_category = get_category( get_option( 'default_category' ) );
                $term = ( is_wp_error( $default_category ) ? '' : $default_category->slug );
            }
        }

        $tags[$tax] = $term;
    }

    /**
     * Filter taxonomy rewrite tags.
     *
     * @param array   $tags       The rewrite tags.
     * @param string  $post_link  A post permalink to resolve from.
     */
    $tags = apply_filters( 'rewrite_tags/taxonomy', $tags, $post_link );

    if ( $post instanceof WP_Post ) {
        $post_type = $post->post_type;

        /**
         * Filter taxonomy rewrite tags for a specific post type.
         *
         * @param array    $tags       The rewrite tags.
         * @param string   $post_link  A post permalink to resolve from.
         * @param WP_Post  $post       The post to resolve from.
         */
        $tags = apply_filters( "rewrite_tags_for_{$post_type}/taxonomy", $tags, $post_link, $post );
    }

    return $tags;
}
