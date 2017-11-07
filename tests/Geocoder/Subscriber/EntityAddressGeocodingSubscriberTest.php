<?php

namespace Tests\AppBundle\Geocoder\Subscriber;

use AppBundle\Committee\CommitteeWasCreatedEvent;
use AppBundle\Entity\Adherent;
use AppBundle\Entity\Committee;
use AppBundle\Entity\Group;
use AppBundle\Entity\NullablePostAddress;
use AppBundle\Entity\PostAddress;
use AppBundle\Geocoder\GeoPointInterface;
use AppBundle\Geocoder\Subscriber\EntityAddressGeocodingSubscriber;
use AppBundle\Group\GroupWasCreatedEvent;
use AppBundle\Membership\ActivityPositions;
use AppBundle\Membership\AdherentAccountWasCreatedEvent;
use AppBundle\Membership\AdherentProfileWasUpdatedEvent;
use Doctrine\Common\Persistence\ObjectManager;
use libphonenumber\PhoneNumber;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Tests\AppBundle\Test\Geocoder\DummyGeocoder;

class EntityAddressGeocodingSubscriberTest extends TestCase
{
    private $manager;

    /* @var EntityAddressGeocodingSubscriber */
    private $subscriber;

    public function testOnAdherentAccountRegistrationCompletedSucceeds()
    {
        $adherent = $this->createAdherent('92 bld Victor Hugo');

        $this->assertInstanceOf(GeoPointInterface::class, $adherent);
        $this->assertNull($adherent->getLatitude());
        $this->assertNull($adherent->getLongitude());

        $this->manager->expects($this->once())->method('flush');
        $this->subscriber->onAdherentAccountRegistrationCompleted(new AdherentAccountWasCreatedEvent($adherent));

        $this->assertSame(48.901058, $adherent->getLatitude());
        $this->assertSame(2.318325, $adherent->getLongitude());
    }

    public function testOnAdherentAccountRegistrationCompletedFails()
    {
        $adherent = $this->createAdherent('58 rue de Picsou');

        $this->assertInstanceOf(GeoPointInterface::class, $adherent);
        $this->assertNull($adherent->getLatitude());
        $this->assertNull($adherent->getLongitude());

        $this->manager->expects($this->never())->method('flush');
        $this->subscriber->onAdherentAccountRegistrationCompleted(new AdherentAccountWasCreatedEvent($adherent));

        $this->assertNull($adherent->getLatitude());
        $this->assertNull($adherent->getLongitude());
    }

    public function testOnAdherentProfileUpdatedWithSameAddressDoNothing()
    {
        $adherent = $this->createAdherent('92 bld Victor Hugo');

        $this->manager->expects($this->once())->method('flush');
        $this->subscriber->onAdherentAccountRegistrationCompleted(new AdherentAccountWasCreatedEvent($adherent));

        $this->assertSame(48.901058, $adherent->getLatitude());
        $this->assertSame(2.318325, $adherent->getLongitude());

        $this->manager->expects($this->never())->method('flush');
        $this->subscriber->onAdherentProfileUpdated(new AdherentProfileWasUpdatedEvent($adherent));

        $this->assertSame(48.901058, $adherent->getLatitude());
        $this->assertSame(2.318325, $adherent->getLongitude());
    }

    public function testOnAdherentProfileUpdatedWithNewAddressSucceeds()
    {
        $adherent = $this->createAdherent('92 bld Victor Hugo');

        $this->assertInstanceOf(GeoPointInterface::class, $adherent);
        $this->assertNull($adherent->getLatitude());
        $this->assertNull($adherent->getLongitude());

        $this->manager->expects($this->once())->method('flush');
        $this->subscriber->onAdherentProfileUpdated(new AdherentProfileWasUpdatedEvent($adherent));

        $this->assertSame(48.901058, $adherent->getLatitude());
        $this->assertSame(2.318325, $adherent->getLongitude());
    }

    public function testOnCommitteeCreatedSucceeds()
    {
        $committee = $this->createCommittee('6 rue Neyret');

        $this->assertInstanceOf(GeoPointInterface::class, $committee);
        $this->assertNull($committee->getLatitude());
        $this->assertNull($committee->getLongitude());

        $this->manager->expects($this->once())->method('flush');
        $this->subscriber->onCommitteeCreated(new CommitteeWasCreatedEvent($committee, $this->createAdherent('92 bld Victor Hugo')));

        $this->assertSame(45.7713288, $committee->getLatitude());
        $this->assertSame(4.8288758, $committee->getLongitude());
    }

    public function testOnCommitteeCreatedFailed()
    {
        $committee = $this->createCommittee('12 rue Jean Paul II');

        $this->assertInstanceOf(GeoPointInterface::class, $committee);
        $this->assertNull($committee->getLatitude());
        $this->assertNull($committee->getLongitude());

        $this->manager->expects($this->never())->method('flush');
        $this->subscriber->onCommitteeCreated(new CommitteeWasCreatedEvent($committee, $this->createAdherent('92 bld Victor Hugo')));

        $this->assertNull($committee->getLatitude());
        $this->assertNull($committee->getLongitude());
    }

    public function testOnGroupCreatedSucceeds()
    {
        $group = $this->createGroup('6 rue Neyret');

        $this->assertInstanceOf(GeoPointInterface::class, $group);
        $this->assertNull($group->getLatitude());
        $this->assertNull($group->getLongitude());

        $this->manager->expects($this->once())->method('flush');
        $this->subscriber->onGroupCreated(new GroupWasCreatedEvent($group, $this->createAdherent('92 bld Victor Hugo')));

        $this->assertSame(45.7713288, $group->getLatitude());
        $this->assertSame(4.8288758, $group->getLongitude());
    }

    public function testOnGroupCreatedWithoutAddressSucceeds()
    {
        $group = $this->createGroup();

        $this->assertInstanceOf(GeoPointInterface::class, $group);
        $this->assertNull($group->getLatitude());
        $this->assertNull($group->getLongitude());

        $this->manager->expects($this->never())->method('flush');
        $this->subscriber->onGroupCreated(new GroupWasCreatedEvent($group, $this->createAdherent('92 bld Victor Hugo')));

        $this->assertNull($group->getLatitude());
        $this->assertNull($group->getLongitude());
    }

    public function testOnGroupCreatedFailed()
    {
        $group = $this->createGroup('12 rue Jean Paul II');

        $this->assertInstanceOf(GeoPointInterface::class, $group);
        $this->assertNull($group->getLatitude());
        $this->assertNull($group->getLongitude());

        $this->manager->expects($this->never())->method('flush');
        $this->subscriber->onGroupCreated(new GroupWasCreatedEvent($group, $this->createAdherent('92 bld Victor Hugo')));

        $this->assertNull($group->getLatitude());
        $this->assertNull($group->getLongitude());
    }

    private function createCommittee(string $address): Committee
    {
        $committee = new Committee(
            Uuid::fromString('30619ef2-cc3c-491e-9449-f795ef109898'),
            Uuid::fromString('d3522426-1bac-4da4-ade8-5204c9e2caae'),
            'En Marche ! - Lyon',
            'Le comité En Marche ! de Lyon village',
            PostAddress::createFrenchAddress($address, '69001-69381'),
            (new PhoneNumber())->setCountryCode('FR')->setNationalNumber('0407080502'),
            '69001-en-marche-clichy'
        );

        return $committee;
    }

    private function createGroup(string $address = null): Group
    {
        $group = new Group(
            Uuid::fromString('7eaa4d91-aec7-4b0d-b6f6-50ff6d77c082'),
            Uuid::fromString('6c77b5f9-52e8-4502-85fd-1f2316c2764b'),
            'MOOC à Lyon',
            'L\'équipe MOOC à Lyon village',
            $address ? NullablePostAddress::createFrenchAddress($address, '69001-69381') : null,
            (new PhoneNumber())->setCountryCode('FR')->setNationalNumber('040708050&'),
            '69001-mooc-lyon'
        );

        return $group;
    }

    private function createAdherent(string $address): Adherent
    {
        return new Adherent(
            Uuid::fromString('d3522426-1bac-4da4-ade8-5204c9e2caae'),
            'john.smith@example.org',
            'super-password',
            'male',
            'John',
            'Smith',
            new \DateTime('1990-12-12'),
            ActivityPositions::STUDENT,
            PostAddress::createFrenchAddress($address, '92110-92024')
        );
    }

    protected function setUp()
    {
        parent::setUp();

        $this->manager = $this->getMockBuilder(ObjectManager::class)->getMock();
        $this->subscriber = new EntityAddressGeocodingSubscriber(new DummyGeocoder(), $this->manager);
    }

    protected function tearDown()
    {
        $this->manager = null;
        $this->subscriber = null;

        parent::tearDown();
    }
}
