<?php

namespace Tests\AppBundle\Controller\Amp;

use AppBundle\DataFixtures\ORM\LoadArticleData;
use AppBundle\DataFixtures\ORM\LoadOrderArticleData;
use AppBundle\DataFixtures\ORM\LoadOrderSectionData;
use AppBundle\DataFixtures\ORM\LoadProposalData;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\AppBundle\Controller\ControllerTestTrait;
use Tests\AppBundle\SqliteWebTestCase;

/**
 * @group functional
 * @group amp
 */
class AmpControllerTest extends SqliteWebTestCase
{
    use ControllerTestTrait;

    public function testArticlePublished()
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/articles/actualites/outre-mer');

        $this->assertResponseStatusCode(Response::HTTP_OK, $response = $this->client->getResponse());
        $this->assertSame(1, $crawler->filter('html:contains("An exhibit of Markdown")')->count());
        $this->assertContains('<amp-img src="/assets/images/article.jpg', $this->client->getResponse()->getContent());
    }

    public function testArticleDraft()
    {
        $this->client->request(Request::METHOD_GET, '/articles/actualites/brouillon');
        $this->assertResponseStatusCode(Response::HTTP_NOT_FOUND, $this->client->getResponse());
    }

    public function testRedirectionToArticle()
    {
        $this->client = $this->makeClient(false, ['HTTP_HOST' => $this->hosts['app']]);
        $this->client->request(Request::METHOD_GET, '/amp/article/outre-mer');

        $this->assertResponseStatusCode(Response::HTTP_MOVED_PERMANENTLY, $this->client->getResponse());

        $this->assertClientIsRedirectedTo('//'.$this->hosts['amp'].'/articles/actualites/outre-mer', $this->client);
        $this->client->followRedirect();

        $this->assertResponseStatusCode(Response::HTTP_OK, $response = $this->client->getResponse());
    }

    public function testRedirectionToProposal()
    {
        $this->client = $this->makeClient(false, ['HTTP_HOST' => $this->hosts['app']]);
        $this->client->request(Request::METHOD_GET, '/amp/proposition/produire-en-france-et-sauver-la-planete');

        $this->assertResponseStatusCode(Response::HTTP_MOVED_PERMANENTLY, $this->client->getResponse());

        $this->assertClientIsRedirectedTo('//'.$this->hosts['amp'].'/proposition/produire-en-france-et-sauver-la-planete', $this->client);
        $this->client->followRedirect();

        $this->assertResponseStatusCode(Response::HTTP_OK, $response = $this->client->getResponse());
    }

    public function testRedirectionToOrderArticle()
    {
        $this->client = $this->makeClient(false, ['HTTP_HOST' => $this->hosts['app']]);
        $this->client->request(Request::METHOD_GET, '/amp/transformer-la-france/premiere-article');

        $this->assertResponseStatusCode(Response::HTTP_MOVED_PERMANENTLY, $this->client->getResponse());

        $this->assertClientIsRedirectedTo('//'.$this->hosts['amp'].'/transformer-la-france/premiere-article', $this->client);
        $this->client->followRedirect();

        $this->assertResponseStatusCode(Response::HTTP_OK, $response = $this->client->getResponse());
    }

    public function testProposalPublished()
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/proposition/produire-en-france-et-sauver-la-planete');

        $this->assertResponseStatusCode(Response::HTTP_OK, $response = $this->client->getResponse());
        $this->assertSame(1, $crawler->filter('html:contains("An exhibit of Markdown")')->count());
        $this->assertContains('<amp-img src="/assets/images/proposal.jpg', $this->client->getResponse()->getContent());
    }

    public function testProposalDraft()
    {
        $this->client->request(Request::METHOD_GET, '/proposition/mieux-vivre-de-son-travail');
        $this->assertResponseStatusCode(Response::HTTP_NOT_FOUND, $this->client->getResponse());
    }

    public function testOrderArticlePublished()
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/transformer-la-france/premiere-article');

        $this->assertResponseStatusCode(Response::HTTP_OK, $response = $this->client->getResponse());
        $this->assertSame(1, $crawler->filter('html:contains("An exhibit of Markdown")')->count());
        $this->assertContains('<amp-img src="/assets/images/order_article.jpg', $this->client->getResponse()->getContent());
    }

    public function testOrderArticleDraft()
    {
        $this->client->request(Request::METHOD_GET, '/transformer-la-france/brouillon');
        $this->assertResponseStatusCode(Response::HTTP_NOT_FOUND, $this->client->getResponse());
    }

    public function testSitemap()
    {
        $this->client->request(Request::METHOD_GET, '/sitemap.xml');
        $this->assertResponseStatusCode(Response::HTTP_OK, $this->client->getResponse());
    }

    protected function setUp()
    {
        parent::setUp();

        $this->init([
            LoadOrderSectionData::class,
            LoadOrderArticleData::class,
            LoadArticleData::class,
            LoadProposalData::class,
        ], 'amp');
    }

    protected function tearDown()
    {
        $this->kill();

        parent::tearDown();
    }
}
