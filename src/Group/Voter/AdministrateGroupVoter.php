<?php

namespace AppBundle\Group\Voter;

use AppBundle\Group\GroupManager;
use AppBundle\Group\GroupPermissions;
use AppBundle\Entity\Adherent;
use AppBundle\Entity\Group;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class AdministrateGroupVoter extends Voter
{
    private $manager;

    public function __construct(GroupManager $manager)
    {
        $this->manager = $manager;
    }

    protected function supports($attribute, $subject): bool
    {
        return GroupPermissions::ADMINISTRATE === $attribute && $subject instanceof Group;
    }

    /**
     * @param string         $attribute
     * @param Group          $group
     * @param TokenInterface $token
     *
     * @return bool
     */
    protected function voteOnAttribute($attribute, $group, TokenInterface $token): bool
    {
        if ($token instanceof AnonymousToken) {
            return false;
        }

        $administrator = $token->getUser();

        if (!$administrator instanceof Adherent) {
            return false;
        }

        return $this->manager->administrateGroup($administrator, $group);
    }
}
