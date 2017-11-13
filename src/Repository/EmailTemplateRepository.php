<?php

namespace AppBundle\Repository;

use AppBundle\Entity\EmailTemplate;
use Doctrine\ORM\EntityRepository;

class EmailTemplateRepository extends EntityRepository
{
    public function save(EmailTemplate $template)
    {
        if (!$template->getId()) {
            $this->_em->persist($template);
        }

        $this->_em->flush();
    }
}
