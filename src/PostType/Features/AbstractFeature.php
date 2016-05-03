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

use Thelonius\Entity\Entity;
use Thelonius\Contracts\PostType\Feature as FeatureInterface;

/**
 * Abstract Post Type Feature
 *
 * For defining a supportable feature for a given post type.
 *
 * @global array $_wp_post_type_features
 *
 * @link https://codex.wordpress.org/Function_Reference/add_post_type_support
 * @link https://codex.wordpress.org/Function_Reference/post_type_supports
 */
abstract class AbstractFeature extends Entity implements
    FeatureInterface
{
    /**
     * The type of model.
     *
     * @var string
     */
    const ENTITY_TYPE = 'post_type_feature';

    /**
     * The feature key.
     *
     * @var string
     */
    const FEATURE_NAME = '';

    /**
     * The feature settings.
     *
     * @var array
     */
    protected $options = [];

    /**
     * Create a new WordPress post type feature instance.
     *
     * @param  array  $attributes  Array arguments for customizing the feature.
     * @return void
     */
    public function __construct( array $options = [] )
    {
        //merge and save options so we atleast have default values
        //unsued options will be ignored anyway
        $this->options = array_merge( $this->defaultOptions(), $options );

        $this->bootIfNotBooted();
    }

    /**
     * Retrieve the default options for the feature.
     *
     * @return array
     */
    protected function defaultOptions()
    {
        return [
            'ignored_post_types' => []
        ];
    }

    /**
     * Retrieve the post type objects that support this feature.
     *
     * @return array
     */
    protected function getPostTypes()
    {
        $post_types = get_post_types_by_support( static::FEATURE_NAME );

        if ( is_array( $this->options['ignored_post_types'] ) ) {
            $post_types = array_diff($post_types, $this->options['ignored_post_types']);
            $post_types = array_values($post_types);
        }

        return array_map([ $this, 'getPostTypeObject' ], $post_types);
    }

    /**
     * Retrieve the post type objects that support this feature.
     *
     * @param  string  $post_type  The name of a registered post type.
     * @return object|null  A post type object.
     */
    private function getPostTypeObject( $post_type )
    {
        return get_post_type_object( $post_type );
    }
}
