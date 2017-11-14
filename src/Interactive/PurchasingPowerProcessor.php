<?php

namespace AppBundle\Interactive;

use AppBundle\Entity\PurchasingPowerChoice;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Validator\Constraints as Assert;

final class PurchasingPowerProcessor
{
    const STATE_NEEDS_FRIEND_INFO = 'needs_friend_info';
    const STATE_NEEDS_FRIEND_CASES = 'needs_friend_cases';
    const STATE_NEEDS_FRIEND_APPRECIATIONS = 'needs_friend_appreciations';
    const STATE_SUMMARY = 'summary';
    const STATE_SENT = 'sent';

    const STATES = [
        self::STATE_NEEDS_FRIEND_INFO,
        self::STATE_NEEDS_FRIEND_CASES,
        self::STATE_NEEDS_FRIEND_APPRECIATIONS,
        self::STATE_SUMMARY,
        self::STATE_SENT,
    ];

    const TRANSITION_FILL_INFO = 'fill_info';
    const TRANSITION_FILL_CASES = 'fill_cases';
    const TRANSITION_FILL_APPRECIATIONS = 'fill_appreciations';
    const TRANSITION_SEND = 'send';

    const TRANSITIONS = [
        self::TRANSITION_FILL_INFO,
        self::TRANSITION_FILL_CASES,
        self::TRANSITION_FILL_APPRECIATIONS,
        self::TRANSITION_SEND,
    ];

    /**
     * @Assert\NotBlank(groups={"fill_info"})
     * @Assert\Type("string", groups={"fill_info"})
     * @Assert\Length(max=50, groups={"fill_info"})
     */
    public $friendFirstName = '';

    /**
     * @Assert\NotBlank(groups={"fill_info"})
     * @Assert\Type("integer", groups={"fill_info"})
     * @Assert\Range(min=17, groups={"fill_info"})
     */
    public $friendAge = 0;

    /**
     * @Assert\NotBlank(groups={"fill_info"})
     * @Assert\Choice(callback={"AppBundle\ValueObject\Genders", "all"}, strict=true, groups={"fill_info"})
     */
    public $friendGender;

    /**
     * @var PurchasingPowerChoice|null
     *
     * @Assert\NotBlank(groups={"fill_info"})
     * @Assert\Type("AppBundle\Entity\PurchasingPowerChoice", groups={"fill_info"})
     */
    public $friendPosition;

    /**
     * @Assert\Count(min=2, max=2, exactMessage="interactive.friend_cases.count", groups={"fill_cases"})
     * @Assert\All({
     *     @Assert\Type("AppBundle\Entity\PurchasingPowerChoice")
     * }, groups={"fill_cases"})
     */
    public $friendCases = [];

    /**
     * @Assert\Count(min=2, max=2, exactMessage="interactive.friend_appreciations.count", groups={"fill_appreciations"})
     * @Assert\All({
     *     @Assert\Type("AppBundle\Entity\PurchasingPowerChoice")
     * }, groups={"fill_appreciations"})
     */
    public $friendAppreciations;

    /**
     * @Assert\NotBlank(groups={"send"})
     * @Assert\Type("string", groups={"send"})
     * @Assert\Length(max=100, groups={"send"})
     */
    public $messageSubject = '';

    /**
     * @Assert\NotBlank(groups={"send"})
     * @Assert\Type("string", groups={"send"})
     */
    public $messageContent = '';

    /**
     * @Assert\NotBlank(groups={"send"})
     * @Assert\Type("string", groups={"send"})
     * @Assert\Length(max=50, groups={"send"})
     */
    public $selfFirstName = '';

    /**
     * @Assert\NotBlank(groups={"send"})
     * @Assert\Type("string", groups={"send"})
     * @Assert\Length(max=50, groups={"send"})
     */
    public $selfLastName = '';

    /**
     * @Assert\NotBlank(groups={"send"})
     * @Assert\Email(groups={"send"})
     * @Assert\Length(max=255, maxMessage="common.email.max_length", groups={"send"})
     */
    public $selfEmail = '';

    /**
     * @Assert\NotBlank(groups={"send"})
     * @Assert\Email(groups={"send"})
     * @Assert\Length(max=255, maxMessage="common.email.max_length", groups={"send"})
     */
    public $friendEmail = '';

    /**
     * Handled by the workflow.
     *
     * @var string
     */
    public $marking;

    /**
     * @return string[]
     */
    public function getArguments(): array
    {
        foreach (PurchasingPowerChoice::getStepsOrderForEmail() as $step) {
            switch ($step) {
                case PurchasingPowerChoice::STEP_FRIEND_PROFESSIONAL_POSITION:
                    $choices = [$this->friendPosition];

                    break;
                case PurchasingPowerChoice::STEP_FRIEND_CASES:
                    $choices = $this->friendCases;

                    break;
                case PurchasingPowerChoice::STEP_FRIEND_APPRECIATIONS:
                    $choices = $this->friendAppreciations;

                    break;
                default:
                    // Not handled
                    continue;
            }

            foreach ($choices as $choice) {
                $arguments[] = $choice->getContent();
            }
        }

        return $arguments ?? [];
    }

    public function defineChoices(Collection $collection): void
    {
        // Ensure the collection is new
        $collection->clear();

        if ($this->friendPosition) {
            $collection->add($this->friendPosition);
        }
        foreach ($this->friendCases as $interest) {
            $collection->add($interest);
        }
        foreach ($this->friendAppreciations as $reason) {
            $collection->add($reason);
        }
    }

    public function refreshChoices(ObjectManager $manager): void
    {
        if ($this->friendPosition) {
            $this->friendPosition = $manager->merge($this->friendPosition);
        }

        $refreshedCases = [];
        foreach ($this->friendCases as $interest) {
            $refreshedCases[] = $manager->merge($interest);
        }
        $this->friendCases = $refreshedCases;

        $refreshedReasons = [];
        foreach ($this->friendAppreciations as $reason) {
            $refreshedReasons[] = $manager->merge($reason);
        }
        $this->friendAppreciations = $refreshedReasons;
    }
}
