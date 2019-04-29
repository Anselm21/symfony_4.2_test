<?php

namespace App\Form\DataTransformer;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class UserToNumberTransformer implements DataTransformerInterface
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Transforms an object (user) to a string (number).
     *
     * @param User|null $user
     * @return string
     */
    public function transform($user)
    {
        if (null === $user) {
            return '';
        }

        return $user->getId();
    }

    /**
     * Transforms a string (number) to an object (user).
     *
     * @param string $userNumber
     * @return User|null
     * @throws TransformationFailedException if object (user) is not found.
     */
    public function reverseTransform($userNumber)
    {

        if (!$userNumber) {
            return;
        }

        $user = $this->entityManager
            ->getRepository(User::class)
            ->find($userNumber);

        if (null === $user) {
            throw new TransformationFailedException(sprintf(
                'An user with number "%s" does not exist!',
                $userNumber
            ));
        }

        return $user;
    }
}