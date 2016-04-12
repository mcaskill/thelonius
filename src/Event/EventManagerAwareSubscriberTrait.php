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

namespace Thelonius\Event;

use Thelonius\Event\EventManager;
use Thelonius\Event\SubscriberInterface;

/**
 * An event subscriber who stores an instance of the WordPress event manager
 * so that it can trigger additional events.
 *
 * Basic implementation of {@see EventManagerAwareSubscriberInterface}.
 *
 * @author Carl Alexander <contact@carlalexander.ca>
 * @link   https://carlalexander.ca/design-system-wordpress-event-management
 *         Based on Carl Alexander's examples from "Design a system: WordPress event management".
 */
trait EventManagerAwareSubscriberTrait
{
    /**
     * The WordPress event manager.
     *
     * @var EventManager
     */
    protected $eventManager;

    /**
     * Set the WordPress event manager for the subscriber.
     *
     * @param EventManager  $manager  The WordPress event manager.
     *
     * @return self
     */
    public function setEventManager(EventManager $manager)
    {
        $this->eventManager = $manager;

        return $this;
    }
}
