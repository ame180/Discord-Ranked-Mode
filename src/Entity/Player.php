<?php

namespace App\Entity;

use App\Repository\PlayerRepository;
use App\Service\DiscordAvatarUrlResolver;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;

#[ORM\Entity(repositoryClass: PlayerRepository::class)]
#[ORM\Table(name: 'players')]
#[ORM\Index(fields: ['externalId'], name: 'external_id_idx')]
class Player implements JsonSerializable
{
    #[ORM\Id]
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\Column(name: 'username', type: 'string')]
    private string $username;

    #[ORM\Column(name: 'external_id', type: 'string', unique: true)]
    private string $externalId;

    #[ORM\OneToMany(mappedBy: 'player', targetEntity: PlayerSnapshot::class, cascade: ['all'], orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'ASC'])]
    /** @var Collection<int, PlayerSnapshot> $snapshots */
    private Collection $snapshots;

    #[ORM\Column(name: 'avatar', type: 'string', nullable: true)]
    private ?string $avatar;

    public function __construct()
    {
        $this->snapshots = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getExternalId(): string
    {
        return $this->externalId;
    }

    public function setExternalId(string $externalId): self
    {
        $this->externalId = $externalId;

        return $this;
    }

    /**
     * @return Collection<int, PlayerSnapshot>
     */
    public function getSnapshots(): Collection
    {
        return $this->snapshots;
    }

    public function addSnapshot(PlayerSnapshot $snapshot): self
    {
        $this->snapshots[] = $snapshot;

        return $this;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): self
    {
        $this->avatar = $avatar;

        return $this;
    }

    public function getAvatarUrl(): string
    {
        return DiscordAvatarUrlResolver::resolveAvatarUrl($this->externalId, $this->avatar);
    }

    public function getXp(): int
    {
        return $this->snapshots->last()->getXp();
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'externalId' => $this->externalId,
        ];
    }
}
