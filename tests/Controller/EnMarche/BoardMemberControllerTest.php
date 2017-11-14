<?php

namespace Tests\AppBundle\Controller\EnMarche;

use AppBundle\DataFixtures\ORM\LoadAdherentData;
use AppBundle\DataFixtures\ORM\LoadBoardMemberRoleData;
use AppBundle\Mailjet\Message\BoardMemberMessage;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\AppBundle\Controller\ControllerTestTrait;
use Tests\AppBundle\SqliteWebTestCase;

/**
 * @group functional
 * @group boardMember
 */
class BoardMemberControllerTest extends SqliteWebTestCase
{
    use ControllerTestTrait;

    public function testUnauthorizeToAccessOnBoardMemberArea()
    {
        $this->authenticateAsAdherent($this->client, 'michel.vasseur@example.ch', 'secret!12345');

        $this->client->request(Request::METHOD_GET, '/espace-membres-conseil/');
        $this->assertResponseStatusCode(Response::HTTP_FORBIDDEN, $this->client->getResponse());

        $this->client->request(Request::METHOD_GET, '/espace-membres-conseil/recherche');
        $this->assertResponseStatusCode(Response::HTTP_FORBIDDEN, $this->client->getResponse());

        $this->client->request(Request::METHOD_GET, '/espace-membres-conseil/profils-sauvegardes');
        $this->assertResponseStatusCode(Response::HTTP_FORBIDDEN, $this->client->getResponse());

        $this->client->request(Request::METHOD_GET, '/espace-membres-conseil/recherche/message');
        $this->assertResponseStatusCode(Response::HTTP_FORBIDDEN, $this->client->getResponse());

        $this->client->request(Request::METHOD_GET, '/espace-membres-conseil/profils-sauvegardes/message');
        $this->assertResponseStatusCode(Response::HTTP_FORBIDDEN, $this->client->getResponse());

        $this->client->request(Request::METHOD_GET, '/espace-membres-conseil/message/2');
        $this->assertResponseStatusCode(Response::HTTP_FORBIDDEN, $this->client->getResponse());

        $this->client->request(Request::METHOD_POST, '/espace-membres-conseil/list/boardmember');
        $this->assertResponseStatusCode(Response::HTTP_FORBIDDEN, $this->client->getResponse());

        $this->client->request(Request::METHOD_DELETE, '/espace-membres-conseil/list/boardmember/2');
        $this->assertResponseStatusCode(Response::HTTP_FORBIDDEN, $this->client->getResponse());
    }

    public function testIndexBoardMember()
    {
        $this->authenticateAsAdherent($this->client, 'referent@en-marche-dev.fr', 'referent');

        $crawler = $this->client->request(Request::METHOD_GET, '/espace-membres-conseil/');

        $this->assertResponseStatusCode(Response::HTTP_OK, $this->client->getResponse());
        $this->assertContains('Referent', $crawler->filter('h1')->text());
    }

    public function testSearchBoardMember()
    {
        $this->authenticateAsBoardMember();

        $crawler = $this->client->request(Request::METHOD_GET, '/espace-membres-conseil/recherche');
        $this->assertResponseStatusCode(Response::HTTP_OK, $this->client->getResponse());

        $resultRow = $crawler->filter('.spaces__results__row');
        $this->assertResponseStatusCode(Response::HTTP_OK, $this->client->getResponse());
        $this->assertSame(4, $resultRow->count());
        $this->assertSame('Laura Deloche', $resultRow->eq(0)->filter('li')->eq(1)->filter('.text--bold')->first()->text());
        $this->assertSame('44, F, Rouen', $resultRow->eq(0)->filter('li')->eq(1)->filter('div')->eq(1)->text());
        $this->assertSame('Martine Lindt', $resultRow->eq(1)->filter('li')->eq(1)->filter('.text--bold')->first()->text());
        $this->assertSame('17, F, Berlin', $resultRow->eq(1)->filter('li')->eq(1)->filter('div')->eq(1)->text());
        $this->assertSame('Élodie Dutemps', $resultRow->eq(2)->filter('li')->eq(1)->filter('.text--bold')->first()->text());
        $this->assertSame('15, F, Singapour', $resultRow->eq(2)->filter('li')->eq(1)->filter('div')->eq(1)->text());
        $this->assertSame('Tous les résultats (4)', $crawler->filter('h2')->first()->text());
        $this->assertSame('Referent Referent', $resultRow->eq(3)->filter('li')->eq(1)->filter('.text--bold')->first()->text());
        $this->assertSame('55, M, Melun', $resultRow->eq(3)->filter('li')->eq(1)->filter('div')->eq(1)->text());

        // Gender
        $this->client->submit($this->client->getCrawler()->selectButton('Rechercher')->form(['g' => 'male']));
        $this->assertResponseStatusCode(Response::HTTP_OK, $this->client->getResponse());

        $results = $this->client->getCrawler()->filter('.spaces__results__row');
        $this->assertCount(1, $results);
        $this->assertContains('Referent Referent', $results->first()->text());

        // Age
        $this->client->submit($this->client->getCrawler()->selectButton('Rechercher')->form([
            'g' => null,
            'amin' => 43,
            'amax' => 45,
        ]));
        $this->assertResponseStatusCode(Response::HTTP_OK, $this->client->getResponse());

        $results = $this->client->getCrawler()->filter('.spaces__results__row');
        $this->assertCount(1, $results);
        $this->assertContains('Laura Deloche', $results->first()->text());

        // Name
        $this->client->submit($this->client->getCrawler()->selectButton('Rechercher')->form([
            'amin' => null,
            'amax' => null,
            'f' => 'Martine',
            'l' => 'Lindt',
        ]));
        $this->assertResponseStatusCode(Response::HTTP_OK, $this->client->getResponse());

        $results = $this->client->getCrawler()->filter('.spaces__results__row');
        $this->assertCount(1, $results);
        $this->assertContains('Martine Lindt', $results->first()->text());

        // Postal Code
        $this->client->submit($this->client->getCrawler()->selectButton('Rechercher')->form([
            'f' => null,
            'l' => null,
            'p' => '368645',
        ]));
        $this->assertResponseStatusCode(Response::HTTP_OK, $this->client->getResponse());

        $results = $this->client->getCrawler()->filter('.spaces__results__row');
        $this->assertCount(1, $results);
        $this->assertContains('Élodie Dutemps', $results->first()->text());

        // Area
        $form = $this->client->getCrawler()->selectButton('Rechercher')->form();
        $form['a[0]']->tick();
        $form['p'] = null;
        $this->client->submit($form);
        $this->assertResponseStatusCode(Response::HTTP_OK, $this->client->getResponse());

        $results = $this->client->getCrawler()->filter('.spaces__results__row');
        $this->assertCount(2, $results);
        $this->assertContains('Laura Deloche', $results->first()->text());
        $this->assertContains('Referent Referent', $results->eq(1)->text());

        // Role
        $form = $this->client->getCrawler()->selectButton('Rechercher')->form();
        $form['a[0]']->untick();
        $form['r[2]']->tick();
        $this->client->submit($form);
        $this->assertResponseStatusCode(Response::HTTP_OK, $this->client->getResponse());

        $results = $this->client->getCrawler()->filter('.spaces__results__row');
        $this->assertCount(1, $results);
        $this->assertContains('Referent Referent', $results->first()->text());
    }

    public function testSavedProfilBoardMember()
    {
        $this->authenticateAsBoardMember();

        $crawler = $this->client->request(Request::METHOD_GET, '/espace-membres-conseil/profils-sauvegardes');
        $members = $crawler->filter('.spaces__results__row');

        $this->assertResponseStatusCode(Response::HTTP_OK, $this->client->getResponse());
        $this->assertCount(3, $members);
        $this->assertContains('Laura Deloche', $members->first()->text());
        $this->assertContains('44, F, Rouen', $members->first()->text());
        $this->assertContains('Martine Lindt', $members->eq(1)->text());
        $this->assertContains('17, F, Berlin', $members->eq(1)->text());
        $this->assertContains('Élodie Dutemps', $members->eq(2)->text());
        $this->assertContains('15, F, Singapour', $members->eq(2)->text());
        $this->assertContains('3 profils sauvegardés', $crawler->filter('h2')->eq(1)->text());

        // Statistics
        $stats = $crawler->filter('#saved_board_members_statistics');
        $this->assertContains('100% femmes / 0% hommes', $stats->html());
        $this->assertContains('25 ans de moyenne d\'âge', $stats->html());
        $this->assertContains('33% Métropole / 0% DOM-TOM / 67% Étranger', $stats->html());
    }

    public function testSendMessageToSearchResult()
    {
        $this->authenticateAsBoardMember();

        $this->client->request(Request::METHOD_GET, '/espace-membres-conseil/recherche');
        $this->assertResponseStatusCode(Response::HTTP_OK, $this->client->getResponse());

        $this->client->submit($this->client->getCrawler()->selectButton('Rechercher')->form(['g' => 'male']));
        $this->assertResponseStatusCode(Response::HTTP_OK, $this->client->getResponse());

        $this->client->click($this->client->getCrawler()->selectLink('Envoyer un message à cette personne')->link());
        $this->assertResponseStatusCode(Response::HTTP_OK, $this->client->getResponse());

        $this->client->submit($this->client->getCrawler()->selectButton('Envoyer le message')->form([
            'board_member_message' => [
                'subject' => 'Sujet',
                'content' => 'Message from search',
            ],
        ]));
        $this->assertResponseStatusCode(Response::HTTP_FOUND, $this->client->getResponse());

        $this->assertCount(1, $this->getMailjetEmailRepository()->findMessages(BoardMemberMessage::class));
        $this->assertCountMails(1, BoardMemberMessage::class, 'referent@en-marche-dev.fr');
        $this->assertCountMails(1, BoardMemberMessage::class, 'jemarche@en-marche.fr');
    }

    public function testSendMessageToSavedMembers()
    {
        $this->authenticateAsBoardMember();

        $this->client->request(Request::METHOD_GET, '/espace-membres-conseil/profils-sauvegardes');
        $this->assertResponseStatusCode(Response::HTTP_OK, $this->client->getResponse());

        $this->client->click($this->client->getCrawler()->selectLink('Envoyer un message à ces 3 personnes')->link());
        $this->assertResponseStatusCode(Response::HTTP_OK, $this->client->getResponse());

        $this->assertContains('3 membres du Conseil', $this->client->getResponse()->getContent());
        $this->client->submit($this->client->getCrawler()->selectButton('Envoyer le message')->form([
            'board_member_message' => [
                'subject' => 'Sujet',
                'content' => 'Message for my special members',
            ],
        ]));
        $this->assertResponseStatusCode(Response::HTTP_FOUND, $this->client->getResponse());

        $this->assertCount(1, $this->getMailjetEmailRepository()->findMessages(BoardMemberMessage::class));
        $this->assertCountMails(1, BoardMemberMessage::class, 'laura@deloche.com');
        $this->assertCountMails(1, BoardMemberMessage::class, 'martine.lindt@gmail.com');
        $this->assertCountMails(1, BoardMemberMessage::class, 'lolodie.dutemps@hotnix.tld');
        $this->assertCountMails(1, BoardMemberMessage::class, 'jemarche@en-marche.fr');
    }

    public function testSendMessageToMember()
    {
        $this->authenticateAsBoardMember();

        $this->client->request(Request::METHOD_GET, '/espace-membres-conseil/recherche');
        $this->assertResponseStatusCode(Response::HTTP_OK, $this->client->getResponse());

        $this->client->click($this->client->getCrawler()->filter('.spaces__results__row')->first()->selectLink('Envoyer un message')->link());
        $this->assertResponseStatusCode(Response::HTTP_OK, $this->client->getResponse());

        $this->assertContains('Un membre du Conseil', $this->client->getResponse()->getContent());
        $this->client->submit($this->client->getCrawler()->selectButton('Envoyer le message')->form([
            'board_member_message' => [
                'subject' => 'Sujet',
                'content' => 'Message for one member',
            ],
        ]));
        $this->assertResponseStatusCode(Response::HTTP_FOUND, $this->client->getResponse());

        $this->assertCount(1, $this->getMailjetEmailRepository()->findMessages(BoardMemberMessage::class));
        $this->assertCountMails(1, BoardMemberMessage::class, 'laura@deloche.com');
        $this->assertCountMails(1, BoardMemberMessage::class, 'jemarche@en-marche.fr');
    }

    private function authenticateAsBoardMember()
    {
        $this->authenticateAsAdherent($this->client, 'kiroule.p@blabla.tld', 'politique2017');
    }

    public function testSaveBoardMemberOnList()
    {
        $this->authenticateAsBoardMember();

        $crawler = $this->client->request(Request::METHOD_GET, '/espace-membres-conseil/recherche');
        $idBoardMemberToAdd = $crawler
            ->filter('.spaces__results__row')
            ->eq(3)
            ->filter('.btn-add-member-list')
            ->attr('data-memberid');

        $this->client->request(Request::METHOD_POST, '/espace-membres-conseil/list/boardmember', [
            'boardMemberId' => $idBoardMemberToAdd,
        ]);

        $this->assertResponseStatusCode(Response::HTTP_CREATED, $this->client->getResponse());

        $this->client->request(Request::METHOD_POST, '/espace-membres-conseil/list/boardmember', [
            'boardMember' => 1234,
        ]);

        $this->assertResponseStatusCode(Response::HTTP_BAD_REQUEST, $this->client->getResponse());

        $this->client->request(Request::METHOD_POST, '/espace-membres-conseil/list/boardmember', [
            'boardMemberId' => 99999,
        ]);

        $this->assertResponseStatusCode(Response::HTTP_NOT_FOUND, $this->client->getResponse());
    }

    public function testDeleteBoardMemberOnList()
    {
        $this->authenticateAsBoardMember();
        $this->client->request(Request::METHOD_DELETE, '/espace-membres-conseil/list/boardmember/2');

        $this->assertResponseStatusCode(Response::HTTP_OK, $this->client->getResponse());
        $this->client->request(Request::METHOD_DELETE, '/espace-membres-conseil/list/boardmember/9999');

        $this->assertResponseStatusCode(Response::HTTP_NOT_FOUND, $this->client->getResponse());
    }

    protected function setUp()
    {
        parent::setUp();

        $this->init([
            LoadAdherentData::class,
            LoadBoardMemberRoleData::class,
        ]);
    }

    protected function tearDown()
    {
        $this->kill();

        parent::tearDown();
    }
}
