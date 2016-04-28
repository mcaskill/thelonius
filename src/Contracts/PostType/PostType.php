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
 * Defines a WordPress post type model.
 */
interface PostType
{
    /**
     * The configuration / model for registering the post type.
     *
     * @return array|string|PostTypeModel|PostTypeConfig
     */
    public function getPostTypeConfig();

    /**
     * Retrieve the post data as a {@see wp_insert_post()} compatible array.
     *
     * @return array
     */
    public function getPostData();
}
