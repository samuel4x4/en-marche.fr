<?php

namespace AppBundle\Group\Voter;

use AppBundle\Group\GroupManager;
use AppBundle\Group\GroupPermissions;
use AppBundle\Entity\Adherent;
use AppBundle\Entity\Group;

class FollowGroupVoter extends AbstractGroupVoter
{
    private $manager;

    public function __construct(GroupManager $manager)
    {
        $this->manager = $manager;
    }

    protected function supports($attribute, $subject)
    {
        $attributes = [
            GroupPermissions::FOLLOW,
            GroupPermissions::UNFOLLOW,
        ];

        return in_array(strtoupper($attribute), $attributes, true) && $subject instanceof Group;
    }

    protected function doVoteOnAttribute(string $attribute, Adherent $adherent, Group $group): bool
    {
        if (!$group->isApproved()) {
            return false;
        }

        if (GroupPermissions::FOLLOW === $attribute) {
            return $this->voteOnFollowGroupAttribute($adherent, $group);
        }

        return true;
    }

    private function voteOnFollowGroupAttribute(Adherent $adherent, Group $group): bool
    {
        return !$this->manager->isFollowingGroup($adherent, $group);
    }
}
