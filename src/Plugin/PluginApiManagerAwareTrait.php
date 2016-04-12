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
 * An object who stores an instance of the WordPress plugin API
 * manager so that it can trigger events.
 *
 * Basic implementation of {@see EventManagerAwareSubscriberInterface}.
 *
 * @author Carl Alexander <contact@carlalexander.ca>
 * @link   https://carlalexander.ca/design-system-wordpress-event-management
 *         Based on Carl Alexander's examples from "Design a system: WordPress event management".
 */
trait PluginApiManagerAwareTrait
{
    /**
     * The WordPress event manager.
     *
     * @var EventManager
     */
    protected $pluginApiManager;

    /**
     * Set the WordPress Plugin API manager for the subscriber.
     *
     * @param PluginApiManager  $manager  The WordPress Plugin API manager.
     *
     * @return self
     */
    public function setPluginApiManager(PluginApiManager $manager)
    {
        $this->pluginApiManager = $manager;

        return $this;
    }
}
