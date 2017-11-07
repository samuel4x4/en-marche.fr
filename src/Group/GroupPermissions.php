<?php

namespace AppBundle\Group;

final class GroupPermissions
{
    const SHOW = 'SHOW_GROUP';
    const FOLLOW = 'FOLLOW_GROUP';
    const UNFOLLOW = 'UNFOLLOW_GROUP';
    const ADMINISTRATE = 'ADMINISTRATE_GROUP';

    private function __construct()
    {
    }
}
