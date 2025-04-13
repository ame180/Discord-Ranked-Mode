<?php

namespace App\Controller;

use App\DTO;
use App\Entity;
use App\Repository\PlayerRepository;
use App\Service\LeaderboardProvider\LeaderboardProviderResolver;
use App\Service\PlayerRanksResolver;
use Carbon\Carbon;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\MissingMappingDriverImplementation;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class LeaderboardController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PlayerRepository $playerRepository,
    ) {
    }

    /**
     * @throws \Exception
     */
    #[Route('/{guildIdentifier}', name: 'home', requirements: ['guildIdentifier' => '[a-zA-Z0-9\-_\.]+'])]
    #[Route('{guildIdentifier}/leaderboard', name: 'leaderboard', requirements: ['guildIdentifier' => '[a-zA-Z0-9\-_\.]+'])]
    public function leaderboard(string $guildIdentifier): Response
    {
        /** @var Entity\Guild $guild */
        $guild = $this->entityManager->getRepository(Entity\Guild::class)->findOneBy(['externalId' => $guildIdentifier])
            ?? $this->entityManager->getRepository(Entity\Guild::class)->findOneBy(['slug' => $guildIdentifier]);

        if (!$guild || !$guild->getLeaderboardProvider()) {
            throw new NotFoundHttpException();
        }

        $externalPlayers = LeaderboardProviderResolver::resolveProvider($guild->getLeaderboardProvider())::fetchPlayers(
            $guild->getLeaderboardUrl(),
            $guild->getLeaderboardProviderAuthToken(),
        );
        usort($externalPlayers, fn (DTO\ExternalPlayer $p1, DTO\ExternalPlayer $p2) => $p2->xp <=> $p1->xp);
        $externalPlayers = array_slice($externalPlayers, 0, 100);

        $hasMessageCounts = false;
        foreach ($externalPlayers as $externalPlayer) {
            if (null !== $externalPlayer->messageCount) {
                $hasMessageCounts = true;
                break;
            }
        }

        return $this->render('leaderboard.html.twig', [
            'externalPlayers' => $externalPlayers,
            'hasMessageCounts' => $hasMessageCounts,
        ]);
    }

    #[Route('{guildIdentifier}/ranks', name: 'ranks', requirements: ['guildIdentifier' => '[a-zA-Z0-9\-_\.]+'])]
    public function ranks(string $guildIdentifier): Response
    {
        $guild = $this->entityManager->getRepository(Entity\Guild::class)->findOneBy(['externalId' => $guildIdentifier])
            ?? $this->entityManager->getRepository(Entity\Guild::class)->findOneBy(['slug' => $guildIdentifier]);

        if (!$guild) {
            throw new NotFoundHttpException();
        }

        $playersData = $this->playerRepository->getPlayersWithSnapshotData($guild);

        $playerRankInfos = PlayerRanksResolver::resolvePlayerRanks($playersData);
        /** @var array<string, array<DTO\PlayerRankInfo>> $ranks */
        $ranks = [];
        foreach ($playerRankInfos as $playerRankInfo) {
            $ranks[$playerRankInfo->rank->getName()][] = $playerRankInfo;
        }

        return $this->render('ranks.html.twig', [
            'leaderboard' => $ranks,
        ]);
    }

    /**
     * @throws MissingMappingDriverImplementation
     * @throws Exception
     */
    #[Route('{guildIdentifier}/player/{playerIdentifier}', name: 'player', requirements: ['guildIdentifier' => '[a-zA-Z0-9\-_\.]+', 'playerIdentifier' => '[a-zA-Z0-9\-_\.]+'])]
    public function player(string $guildIdentifier, string $playerIdentifier): Response
    {
        $guild = $this->entityManager->getRepository(Entity\Guild::class)->findOneBy(['externalId' => $guildIdentifier])
            ?? $this->entityManager->getRepository(Entity\Guild::class)->findOneBy(['slug' => $guildIdentifier]);

        /** @var Entity\Player $player */
        $player = $this->playerRepository->findOneBy(['externalId' => $playerIdentifier])
            ?? $this->playerRepository->findOneBy(['username' => $playerIdentifier]);

        if (!$guild || !$player) {
            throw new NotFoundHttpException();
        }

        $daysOnChart = 7;
        $playerSnapshots = $player->getSnapshots($guild);
        $playerSnapshots = $playerSnapshots->filter(function (Entity\PlayerSnapshot $snapshot) use ($daysOnChart) {
            return $snapshot->getCreatedAt()->isAfter(Carbon::now()->subDays($daysOnChart + 1));
        });

        $snapshotDays = [];
        foreach ($playerSnapshots as $snapshot) {
            // Midnight snapshot represents data for the previous day
            $snapshotDays[$snapshot->getCreatedAt()->subHour()->format('Y-m-d')] = $snapshot;
        }

        $xpData = [];
        $messageData = [];
        $chartDay = Carbon::now()->subDays($daysOnChart);
        $previousSnapshot = $snapshotDays[$chartDay->copy()->subDay()->format('Y-m-d')] ?? null;
        $previousXp = $previousSnapshot?->getXp();
        $previousMessageCount = $previousSnapshot?->getMessageCount();

        while ($chartDay->isBefore(Carbon::now()->subDay())) {
            $snapshot = $snapshotDays[$chartDay->format('Y-m-d')] ?? null;
            if (null === $snapshot) {
                $xpData[] = [
                    'date' => $chartDay->format('Y-m-d'),
                    'xp' => null,
                ];

                $messageData[] = [
                    'date' => $chartDay->format('Y-m-d'),
                    'messages' => null,
                ];

                $chartDay->addDay();

                continue;
            }

            $xpData[] = [
                'date' => $snapshot->getCreatedAt()->format('Y-m-d'),
                'xp' => $previousXp ? $snapshot->getXp() - $previousXp : null,
            ];
            $previousXp = $snapshot->getXp();

            // Add message count data
            $messageData[] = [
                'date' => $snapshot->getCreatedAt()->format('Y-m-d'),
                'messages' => null !== $previousMessageCount && null !== $snapshot->getMessageCount()
                    ? $snapshot->getMessageCount() - $previousMessageCount
                    : null,
            ];
            $previousMessageCount = $snapshot->getMessageCount();

            $chartDay->addDay();
        }

        return $this->render('player.html.twig', [
            'player' => $player,
            'xpData' => $xpData,
            'messageData' => $messageData,
        ]);
    }
}
