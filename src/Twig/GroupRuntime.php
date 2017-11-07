<?php

namespace AppBundle\Twig;

use AppBundle\Group\GroupManager;
use AppBundle\Group\GroupPermissions;
use AppBundle\Entity\Adherent;
use AppBundle\Entity\Group;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class GroupRuntime
{
    private $authorizationChecker;
    private $groupManager;

    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        GroupManager $groupManager = null
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->groupManager = $groupManager;
    }

    public function isPromotableAdministrator(Adherent $adherent, Group $group): bool
    {
        if (!$this->groupManager) {
            return false;
        }

        return $this->groupManager->isPromotableAdministrator($adherent, $group);
    }

    public function isDemotableAdministrator(Adherent $adherent, Group $group): bool
    {
        if (!$this->groupManager) {
            return false;
        }

        return $this->groupManager->isDemotableAdministrator($adherent, $group);
    }

    public function isAdministrator(Group $group): bool
    {
        return $this->authorizationChecker->isGranted(GroupPermissions::ADMINISTRATE, $group);
    }

    public function canFollow(Group $group): bool
    {
        return $this->authorizationChecker->isGranted(GroupPermissions::FOLLOW, $group);
    }

    public function canUnfollow(Group $group): bool
    {
        return $this->authorizationChecker->isGranted(GroupPermissions::UNFOLLOW, $group);
    }

    public function canSee(Group $group): bool
    {
        return $this->authorizationChecker->isGranted(GroupPermissions::SHOW, $group);
    }
}
