<?php

namespace AppBundle\Group;

use AppBundle\Address\Address;
use AppBundle\Entity\Group;
use AppBundle\Validator\UniqueGroup as AssertUniqueGroup;
use libphonenumber\PhoneNumber;
use Misd\PhoneNumberBundle\Validator\Constraints\PhoneNumber as AssertPhoneNumber;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @AssertUniqueGroup
 */
class GroupCommand
{
    /** @var Group */
    protected $group;

    /**
     * @Assert\NotBlank
     * @Assert\Length(min=2, max=50)
     */
    public $name;

    /**
     * @Assert\NotBlank
     * @Assert\Length(min=5, max=140, minMessage="group.description.min_length", maxMessage="group.description.max_length")
     */
    public $description;

    /**
     * The group address.
     *
     * @var Address
     *
     * @Assert\Valid
     */
    protected $address;

    /**
     * @AssertPhoneNumber(defaultRegion="FR")
     */
    protected $phone;

    protected function __construct(Address $address = null)
    {
        $this->address = $address;
    }

    public static function createFromGroup(Group $group): self
    {
        $address = $group->getPostAddress() ? Address::createFromAddress($group->getPostAddress()) : null;
        $dto = new self($address);
        $dto->name = $group->getName();
        $dto->description = $group->getDescription();
        $dto->phone = $group->getPhone();
        $dto->group = $group;

        return $dto;
    }

    public function updateGroup(): Group
    {
        if (!$this->group) {
            throw new \RuntimeException('A Group instance is required.');
        }

        $this->group->update($this);
    }

    public function getCityName(): string
    {
        return $this->address->getCityName();
    }

    public function setPhone(PhoneNumber $phone = null): void
    {
        $this->phone = $phone;
    }

    public function getPhone(): ?PhoneNumber
    {
        return $this->phone;
    }

    public function getGroup(): ?Group
    {
        return $this->group;
    }

    public function getGroupUuid(): UuidInterface
    {
        return $this->group->getUuid();
    }

    public function getGroupSlug(): string
    {
        return $this->group->getSlug();
    }

    public function setAddress(Address $address): void
    {
        $this->address = $address;
    }

    public function getAddress(): Address
    {
        return $this->address;
    }
}
