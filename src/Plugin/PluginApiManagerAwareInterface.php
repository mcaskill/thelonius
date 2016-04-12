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

use Thelonius\Plugin\PluginApiManager;

/**
 * An object who can use the WordPress plugin API manager to trigger additional event.
 *
 * @author Carl Alexander <contact@carlalexander.ca>
 * @link   https://carlalexander.ca/design-system-wordpress-event-management
 *         Based on Carl Alexander's examples from "Design a system: WordPress event management".
 */
interface PluginApiManagerAwareInterface
{
    /**
     * Set the WordPress event manager for the subscriber.
     *
     * @param PluginApiManager  $manager  The WordPress Plugin API manager.
     */
    public function setPluginApiManager(PluginApiManager $manager);
}
