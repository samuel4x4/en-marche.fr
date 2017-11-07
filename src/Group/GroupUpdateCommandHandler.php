<?php

namespace AppBundle\Group;

use AppBundle\Address\PostAddressFactory;
use AppBundle\Events;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class GroupUpdateCommandHandler
{
    private $dispatcher;
    private $addressFactory;
    private $manager;

    public function __construct(
        EventDispatcherInterface $dispatcher,
        ObjectManager $manager,
        PostAddressFactory $addressFactory
    ) {
        $this->dispatcher = $dispatcher;
        $this->manager = $manager;
        $this->addressFactory = $addressFactory;
    }

    public function handle(GroupCommand $command)
    {
        if (!$group = $command->getGroup()) {
            throw new \RuntimeException('A Group instance is required.');
        }

        $group->update(
            $command->name,
            $command->description,
            $this->addressFactory->createFromNullableAddress($command->getAddress()),
            $command->getPhone()
        );

        $this->manager->persist($group);
        $this->manager->flush();

        $this->dispatcher->dispatch(Events::GROUP_UPDATED, new GroupWasUpdatedEvent($group));
    }
}
