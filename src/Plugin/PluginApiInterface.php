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

namespace Thelonius\Plugin;

/**
 * Defines an object that can use the WordPress plugin API.
 */
interface PluginApiInterface
{
    const DEFAULT_PRIORITY = 10;
    const DEFAULT_ACCEPTED_ARGS = 1;
}
