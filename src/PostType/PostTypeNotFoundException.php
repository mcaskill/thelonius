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

use Thelonius\Contracts\PostType\PostType;

class PostTypeNotFoundException extends \DomainException
{
    /**
     * Create a new post type exception instance.
     *
     * @param string|object  $postType  The post type name or object with a post type definition.
     * @param Throwable      $previous  The previous exception used for the exception chaining.
     */
    public function __construct($postType, $previous)
    {
        parent::__construct('', 0, $previous);

        $this->postType = $postType;

        if ( $postType instanceof WP_Post ) {
            $postId = $postType->ID;
            $this->postType = $postType = $postType->post_type;
        } elseif ( $postType instanceof PostType ) {
            $postId = $postType->ID;
            $this->postType = $postType = $postType::POST_TYPE;
        } elseif ( ! is_string($postType) ) {
            throw new InvalidArgumentException(sprintf('%s must be passed a post object or post type name.'), basename(__CLASS__) );
        }

        $this->message = sprintf('The post type "%s" does not exist.', $postType );

        if ( $postId ) {
            $this->message .= sprintf(' Invalid post: %s', $postId);
        }
    }
}
