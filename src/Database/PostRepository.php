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

use Thelonius\Database\Repository;

/**
 * Post Repository for the WordPress Database / Query API.
 *
 * The repository manages posts using the WordPress Database / Query APIs.
 */
class PostRepository extends Repository
{
    /**
     * Find posts written by the given author.
     *
     * @param integer|string|WP_User  $author  A user ID, nicename, or {@see WP_User} object.
     * @param integer                 $limit   Optional. The number of posts to query for.
     *                                         Use `-1` to request all posts.
     *
     * @return WP_Post[]
     */
    public function findByAuthor($author, $limit = 10)
    {
        $parameter = 'author';

        if ( is_string($author) ) {
            $parameter = 'author_name';
        } else {
            $author = $this->resolveToID($author);
        }

        return $this->find([
            $parameter       => $author,
            'posts_per_page' => $limit,
        ]);
    }

    /**
     * Find a post using the given post ID.
     *
     * @param integer  $id  Post ID.
     *
     * @return WP_Post|null  Returns NULL if it doesn't find one.
     */
    public function findById($id)
    {
        if ( ! is_int($id) ) {
            throw new InvalidArgumentException('Invalid $id parameter. Must be an integer.');
        }

        return $this->findOne([ 'p' => $id ]);
    }

    /**
     * Insert or update a post in the repository.
     *
     * @uses \WP\wp_insert_post()
     * @uses \WP\wp_update_post()
     *
     * @param array|WP_Post  $post  An array of post data or a {@see WP_Post} object.
     *                              Arrays are expected to be escaped, objects are not.
     *
     * @return integer|WP_Error  Returns the post ID or a {@see WP_Error}.
     */
    public function save($post)
    {
        $postID = $this->resolveToID($post);

        if ($postID) {
            return wp_update_post($post, true);
        }

        return wp_insert_post($post, true);
    }

    /**
     * Insert or update a post in the repository.
     *
     * @uses \WP\wp_insert_post()
     * @uses \WP\wp_update_post()
     *
     * @param WP_Post  $post   A post object.
     * @param boolean  $force  Whether to bypass _trash_ and force deletion.
     *
     * @return integer|WP_Error  Returns the post ID or a {@see WP_Error}.
     */
    public function delete(WP_Post $post, $force = false)
    {
        return wp_delete_post($post->ID, $force);
    }
}
