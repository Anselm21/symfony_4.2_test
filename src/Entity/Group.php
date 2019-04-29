<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Tests\Fixtures\EntityInterfaceA;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="App\Repository\GroupRepository")
 * @ORM\Table(name="`group`")
 * @UniqueEntity(fields={"fullname"}, message="Group with such name already exists!")
 */
class Group
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(name="fullname", type="string", length=255, unique=true)
     */
    private $fullname;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\UserGroup", mappedBy="groupId")
     */
    private $groupUsers;

    const GROUP_SERIALIZE_SCHEME = [
        'attributes' => [
            'id',
            'fullName',
            'groupUsers' => [
                'userId' => [
                    'id',
                    'username',
                    'email']
            ]
        ]
    ];

    public function __construct()
    {
        $this->groupUsers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFullName(): ?string
    {
        return $this->fullname;
    }

    public function setFullName(string $fullname): self
    {
        $this->fullname = $fullname;

        return $this;
    }

    /**
     * @return Collection|UserGroup[]
     */
    public function getGroupUsers(): Collection
    {
        return $this->groupUsers;
    }

    public function addGroupUser(UserGroup $groupUser): self
    {
        if (!$this->groupUsers->contains($groupUser)) {
            $this->groupUsers[] = $groupUser;
            $groupUser->setGroupId($this);
        }

        return $this;
    }

    public function removeGroupUser(UserGroup $groupUser): self
    {
        if ($this->groupUsers->contains($groupUser)) {
            $this->groupUsers->removeElement($groupUser);
            // set the owning side to null (unless already changed)
            if ($groupUser->getGroupId() === $this) {
                $groupUser->setGroupId(null);
            }
        }

        return $this;
    }
}
