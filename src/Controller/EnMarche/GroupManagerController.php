<?php

namespace AppBundle\Controller\EnMarche;

use AppBundle\Group\GroupCommand;
use AppBundle\Entity\Group;
use AppBundle\Form\GroupCommandType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/groups/{slug}")
 * @Security("is_granted('HOST_COMMITTEE', group)")
 */
class GroupManagerController extends Controller
{
    /**
     * @Route("/editer", name="app_group_manager_edit")
     * @Method("GET|POST")
     */
    public function editAction(Request $request, Group $group): Response
    {
        $command = GroupCommand::createFromGroup($group);
        $form = $this->createForm(GroupCommandType::class, $command);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->get('app.group.update_handler')->handle($command);
            $this->addFlash('info', $this->get('translator')->trans('group.update.success'));

            return $this->redirectToRoute('app_group_manager_edit', [
                'slug' => $group->getSlug(),
            ]);
        }

        return $this->render('group_manager/edit.html.twig', [
            'form' => $form->createView(),
            'group' => $group,
            'group_administrators' => $this->get('app.group.manager')->getGroupAdministrators($group),
        ]);
    }
}
