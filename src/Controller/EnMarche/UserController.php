<?php

namespace AppBundle\Controller\EnMarche;

use AppBundle\Form\AdherentEmailSubscriptionType;
use AppBundle\Form\MembershipRequestType;
use AppBundle\Membership\MembershipRequest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/parametres/mon-compte")
 */
class UserController extends Controller
{
    /**
     * @Route("", name="app_user_profile")
     * @Method("GET|POST")
     */
    public function profileOverviewAction(): Response
    {
        return $this->render('user/my_account.html.twig');
    }

    /**
     * @Route("/modifier", name="app_user_edit")
     * @Method("GET|POST")
     */
    public function profileAction(Request $request): Response
    {
        $adherent = $this->getUser();
        $membership = MembershipRequest::createFromAdherent($adherent);
        $form = $this->createForm(MembershipRequestType::class, $membership)
            ->add('submit', SubmitType::class, ['label' => 'Enregistrer les modifications'])
        ;

        if ($form->handleRequest($request)->isSubmitted() && $form->isValid()) {
            $this->get('app.membership_request_handler')->update($adherent, $membership);
            $this->addFlash('info', $this->get('translator')->trans('adherent.update_profile.success'));

            return $this->redirectToRoute('app_user_profile');
        }

        return $this->render('adherent/profile.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * This action enables an adherent to choose his/her email notifications.
     *
     * @Route("/preferences-des-emails", name="app_user_set_email_notifications")
     * @Method("GET|POST")
     */
    public function setEmailNotificationsAction(Request $request): Response
    {
        $form = $this->createForm(AdherentEmailSubscriptionType::class, $this->getUser())
            ->add('submit', SubmitType::class, ['label' => 'Enregistrer les modifications'])
        ;

        if ($form->handleRequest($request)->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();
            $this->addFlash('info', $this->get('translator')->trans('adherent.set_emails_notifications.success'));

            return $this->redirectToRoute('app_user_set_email_notifications');
        }

        return $this->render('user/set_email_notifications.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
