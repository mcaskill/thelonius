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

namespace Thelonius\PostType\Support;

use Thelonius\PostType\Support\FeatureInterface;

/**
 * Abstract Post Type Feature
 *
 * For defining a supportable feature for a given post type.
 *
 * @global array $_wp_post_type_features
 * @link   https://codex.wordpress.org/Function_Reference/add_post_type_support
 * @link   https://codex.wordpress.org/Function_Reference/post_type_supports
 */
abstract class AbstractFeature implements
    FeatureInterface,
    SupportableInterface
{
}
