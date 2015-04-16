<?php
namespace ActionTracker\Event;

use User\Service\UserIdentity as UserIdentityService;
use Application\Event\ApplicationAbstractEvent;

class ActionTrackerEvent extends ApplicationAbstractEvent
{
    /**
     * Activate action event
     */
    const ACTIVATE_ACTION = 'action_tracker_activate';

    /**
     * Deactivate action event
     */
    const DEACTIVATE_ACTION = 'action_tracker_deactivate';

    /**
     * Delete action event
     */
    const DELETE_ACTION = 'action_tracker_delete';

    /**
     * Fire delete action event
     *
     * @param $actionId
     * @return void
     */
    public static function fireDeleteActionEvent($actionId)
    {
        // event's description
        $eventDesc = UserIdentityService::isGuest()
            ? 'Event - Action log deleted by guest'
            : 'Event - Action log deleted by user';

        $eventDescParams = UserIdentityService::isGuest()
            ? [$actionId]
            : [UserIdentityService::getCurrentUserIdentity()['nick_name'], $actionId];

        self::fireEvent(self::DELETE_ACTION, 
                $actionId, UserIdentityService::getCurrentUserIdentity()['user_id'], $eventDesc, $eventDescParams);
    }

    /**
     * Fire activate action event
     *
     * @param $actionId
     * @return void
     */
    public static function fireActivateActionEvent($actionId)
    {
        // event's description
        $eventDesc = UserIdentityService::isGuest()
            ? 'Event - Action activated by guest'
            : 'Event - Action activated by user';

        $eventDescParams = UserIdentityService::isGuest()
            ? [$actionId]
            : [UserIdentityService::getCurrentUserIdentity()['nick_name'], $actionId];

        self::fireEvent(self::ACTIVATE_ACTION, 
                $actionId, UserIdentityService::getCurrentUserIdentity()['user_id'], $eventDesc, $eventDescParams);
    }

    /**
     * Fire deactivate action event
     *
     * @param $actionId
     * @return void
     */
    public static function fireDeactivateActionEvent($actionId)
    {
        // event's description
        $eventDesc = UserIdentityService::isGuest()
            ? 'Event - Action deactivated by guest'
            : 'Event - Action deactivated by user';

        $eventDescParams = UserIdentityService::isGuest()
            ? [$actionId]
            : [UserIdentityService::getCurrentUserIdentity()['nick_name'], $actionId];

        self::fireEvent(self::DEACTIVATE_ACTION, 
                $actionId, UserIdentityService::getCurrentUserIdentity()['user_id'], $eventDesc, $eventDescParams);
    }
}