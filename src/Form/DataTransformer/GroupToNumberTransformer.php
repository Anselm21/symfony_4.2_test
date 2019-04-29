<?php

namespace App\Form\DataTransformer;

use App\Entity\Group;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class GroupToNumberTransformer implements DataTransformerInterface
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Transforms an object (group) to a string (number).
     *
     * @param Group|null $group
     * @return string
     */
    public function transform($group)
    {
        if (null === $group) {
            return '';
        }

        return $group->getId();
    }

    /**
     * Transforms a string (number) to an object (group).
     *
     * @param string $groupNumber
     * @return Group|null
     * @throws TransformationFailedException if object (group) is not found.
     */
    public function reverseTransform($groupNumber)
    {

        if (!$groupNumber) {
            return;
        }

        $group = $this->entityManager
            ->getRepository(Group::class)
            ->find($groupNumber);

        if (null === $group) {
            throw new TransformationFailedException(sprintf(
                'An group with number "%s" does not exist!',
                $groupNumber
            ));
        }

        return $group;
    }
}