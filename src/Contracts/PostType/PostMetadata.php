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
 * Defines a model that can use WordPress post metadata.
 */
interface PostMetadata
{
    /**
     * Retrieve all the post metadata as an associative array.
     *
     * @return array
     */
    public function getPostMeta();
}
