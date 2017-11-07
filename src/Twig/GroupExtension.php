<?php

namespace AppBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class GroupExtension extends AbstractExtension
{
    public function getFunctions()
    {
        return [
            // Permissions
            new TwigFunction('is_administrator', [GroupRuntime::class, 'isAdministrator']),
            new TwigFunction('is_promotable_administrator', [GroupRuntime::class, 'isPromotableAdministrator']),
            new TwigFunction('is_demotable_administrator', [GroupRuntime::class, 'isDemotableAdministrator']),
            new TwigFunction('can_follow', [GroupRuntime::class, 'canFollow']),
            new TwigFunction('can_unfollow', [GroupRuntime::class, 'canUnfollow']),
            new TwigFunction('can_see', [GroupRuntime::class, 'canSee']),
        ];
    }
}
