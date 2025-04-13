<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity()]
#[ORM\Table(name: 'guilds')]
#[ORM\UniqueConstraint(name: 'external_id_uniq', fields: ['externalId'])]
#[ORM\UniqueConstraint(name: 'slug_uniq', fields: ['slug'])]
class Guild
{
    #[ORM\Id]
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\Column(name: 'external_id', type: 'string', unique: true)]
    private string $externalId;

    #[ORM\Column(name: 'name', type: 'string')]
    private string $name;

    #[ORM\Column(name: 'leaderboard_url', type: 'string', nullable: true)]
    private ?string $leaderboardUrl;

    #[ORM\Column(name: 'leaderboard_provider', type: 'string', nullable: true)]
    private ?string $leaderboardProvider;

    #[ORM\Column(name: 'leaderboard_provider_auth_token', type: 'string', nullable: true)]
    private ?string $leaderboardProviderAuthToken;

    #[ORM\Column(name: 'slug', type: 'string', unique: true)]
    private string $slug;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getExternalId(): string
    {
        return $this->externalId;
    }

    public function setExternalId(string $externalId): static
    {
        $this->externalId = $externalId;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getLeaderboardUrl(): ?string
    {
        return $this->leaderboardUrl;
    }

    public function setLeaderboardUrl(?string $leaderboardUrl): static
    {
        $this->leaderboardUrl = $leaderboardUrl;

        return $this;
    }

    public function getLeaderboardProvider(): ?string
    {
        return $this->leaderboardProvider;
    }

    public function setLeaderboardProvider(?string $leaderboardProvider): static
    {
        $this->leaderboardProvider = $leaderboardProvider;

        return $this;
    }

    public function getLeaderboardProviderAuthToken(): ?string
    {
        return $this->leaderboardProviderAuthToken;
    }

    public function setLeaderboardProviderAuthToken(?string $leaderboardProviderAuthToken): static
    {
        $this->leaderboardProviderAuthToken = $leaderboardProviderAuthToken;

        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }
}
