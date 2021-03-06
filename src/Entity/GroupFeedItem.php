<?php

namespace AppBundle\Entity;

use Algolia\AlgoliaSearchBundle\Mapping\Annotation as Algolia;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Table(name="group_feed_items")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\GroupFeedItemRepository")
 *
 * @Algolia\Index(autoIndex=false)
 */
class GroupFeedItem
{
    public const MESSAGE = 'message';
    public const EVENT_MOOC = 'mooc_event';
    public const CITIZEN_INITIATIVE = 'citizen_initiative';

    use EntityIdentityTrait;

    /**
     * @ORM\Column(length=18)
     */
    private $itemType;

    /**
     * @ORM\ManyToOne(targetEntity="Group")
     */
    private $group;

    /**
     * @var Adherent Any administrator of the group
     *
     * @ORM\ManyToOne(targetEntity="Adherent", inversedBy="groupFeedItems")
     */
    private $author;

    /**
     * @var BaseEvent|null
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\BaseEvent", fetch="EAGER")
     * @ORM\JoinColumn(onDelete="CASCADE", nullable=true)
     */
    private $event;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $content;

    /**
     * @ORM\Column(type="boolean", options={"default": true})
     */
    private $published = true;

    /**
     * @var \DateTimeImmutable
     *
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    private function __construct(
        UuidInterface $uuid,
        string $type,
        Group $group,
        Adherent $author,
        bool $published = true,
        string $createdAt = 'now'
    ) {
        $this->uuid = $uuid;
        $this->group = $group;
        $this->author = $author;
        $this->itemType = $type;
        $this->published = $published;
        $this->createdAt = new \DateTime($createdAt);
    }

    public static function createMessage(
        Group $group,
        Adherent $author,
        string $content,
        bool $published = true,
        string $createdAt = 'now'
    ): self {
        $item = new static(Uuid::uuid4(), self::MESSAGE, $group, $author, $published, $createdAt);
        $item->content = $content;

        return $item;
    }

    public static function createEvent(
        Event $event,
        Adherent $author,
        bool $published = true,
        string $createdAt = 'now'
    ): self {
        $item = new static(
            Uuid::uuid5(Uuid::NAMESPACE_OID, (string) $event->getUuid()),
            self::EVENT_MOOC,
            $event->getGroup(),
            $author,
            $published,
            $createdAt
        );
        $item->event = $event;

        return $item;
    }

    public static function createCitizenInitiative(
        Group $group,
        Adherent $author,
        string $content,
        CitizenInitiative $initiative,
        bool $published = true,
        string $createdAt = 'now'
    ): self {
        $item = new static(Uuid::uuid4(), self::CITIZEN_INITIATIVE, $group, $author, $published, $createdAt);
        $item->content = $content;
        $item->event = $initiative;

        return $item;
    }

    public function getContent(): ?string
    {
        if ($this->event instanceof Event) {
            return $this->event->getDescription();
        }

        return $this->content;
    }

    public function getGroup(): Group
    {
        return $this->group;
    }

    public function getEvent(): ?BaseEvent
    {
        return $this->event;
    }

    public function getAuthor(): Adherent
    {
        return $this->author;
    }

    public function getType(): string
    {
        return $this->itemType;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public static function getItemTypes(bool $includeMessages): array
    {
        $types[] = self::EVENT_MOOC;

        if ($includeMessages) {
            $types[] = self::MESSAGE;
        }

        return $types;
    }

    public function getAuthorFirstName(): ?string
    {
        if ($this->author instanceof Adherent) {
            return $this->author->getFirstName();
        }
    }
}
