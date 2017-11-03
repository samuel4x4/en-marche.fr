<?php

namespace AppBundle\Controller\EnMarche;

use AppBundle\Entity\MoocEvent;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/evenement-mooc")
 * @Entity("moocEvent", expr="repository.findOnePublishedBySlug(slug)")
 */
class MoocEventController extends Controller
{
    /**
     * @Route("/{slug}", name="app_mooc_event_show")
     * @Method("GET")
     */
    public function showAction(MoocEvent $moocEvent): Response
    {
        return $this->render('mooc_event/show.html.twig', [
            'mooc_event' => $moocEvent,
        ]);
    }

    /**
     * @Route("/{slug}/invitation", name="app_mooc_event_invite")
     * @Method("GET|POST")
     */
    public function inviteAction(Request $request, MoocEvent $moocEvent): Response
    {
        return new Response();
    }

    /**
     * @Route("/{slug}/ical", name="app_mooc_event_export_ical")
     * @Method("GET")
     */
    public function exportIcalAction(MoocEvent $moocEvent): Response
    {
        return new Response();
    }

    /**
     * @Route("/{slug}/inscription", name="app_mooc_event_attend")
     * @Method("GET|POST")
     */
    public function attendAction(Request $request, MoocEvent $moocEvent): Response
    {
        return new Response();
    }
}
