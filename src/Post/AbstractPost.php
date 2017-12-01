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

namespace Thelonius\Post;

use JsonSerializable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;

use Thelonius\Contracts\PostType\PostMetadata as PostMetadataInterface;
use Thelonius\Contracts\PostType\PostType as PostTypeInterface;
use Thelonius\Contracts\Support\Stringable;

/**
 * A Blog Entry.
 */
abstract class AbstractPost implements
    Arrayable,
    Jsonable,
    JsonSerializable,
    PostTypeInterface,
    PostMetadataInterface,
    Stringable
{
    /**
     * The post type identifier.
     *
     * @var string
     */
    const POST_TYPE = 'post';

    /**
     * The configuration / model for registering the post type.
     *
     * @return array|string|PostTypeModel|PostTypeConfig
     */
    public static function getPostTypeConfig()
    {
        return [];
    }

    /**
     * Retrieve the post data as a {@see wp_insert_post()} compatible array.
     *
     * @return array
     */
    public function getPostData()
    {
        return [
            'post_type'    => self::POST_TYPE,
            'post_status'  => 'publish',
            'post_title'   => $this->name,
            'post_content' => $this->description
        ];
    }

    /**
     * Convert the object into something JSON serializable.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * Convert the object to its JSON representation.
     *
     * @param  integer  $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        # $data = get_object_vars($this);
        $data = $this->getPostData();

        $properties = apply_filters(
            'thelonius/post-type/special-vars',
            [ 'ancestors', 'page_template', 'post_category', 'tags_input' ],
            $this
        );

        foreach ( $properties as $key ) {
            if ( $this->__isset($key) ) {
                $data[$key] = $this->__get($key);
            }
        }

        return $data;
    }

    /**
     * Convert the object to its string representation.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toJson();
    }
}
