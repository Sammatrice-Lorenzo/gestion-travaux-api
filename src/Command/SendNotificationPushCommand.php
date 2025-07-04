<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use App\Repository\TokenNotificationPushRepository;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Interface\Firebase\FirebaseNotificationServiceInterface;

#[AsCommand(
    name: 'app:send-notification-push',
    description: 'Command for schedule a notification push with arguments : title, body',
)]
final class SendNotificationPushCommand extends Command
{
    public function __construct(
        private readonly FirebaseNotificationServiceInterface $firebaseNotificationService,
        private readonly TokenNotificationPushRepository $tokenNotificationPushRepository
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('title', InputArgument::OPTIONAL, 'Title notification')
            ->addArgument('body', InputArgument::OPTIONAL, 'Body notification')
        ;
    }

    private function handleExecuteCommad(SymfonyStyle $io, string $title, string $body): void
    {
        $io->note(sprintf('You passed an argument: %s', $title));
        $io->note(sprintf('You passed an argument: %s', $body));

        $totalNotificationSended = 0;
        $tokenNotificationPushes = $this->tokenNotificationPushRepository->findAll();
        foreach ($tokenNotificationPushes as $tokenNotificationPush) {
            $response = $this->firebaseNotificationService->sendNotification($tokenNotificationPush->getToken(), $title, $body);
            if (array_key_exists('name', $response)) {
                ++$totalNotificationSended;
            }
        }

        $io->success(
            "Notifications push sent {$totalNotificationSended} out of " . count($tokenNotificationPushes)
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $title = $input->getArgument('title');
        $body = $input->getArgument('body');

        if (!$title || !$body) {
            $io->error('Arguments : "title" and "body" are required !');

            return Command::INVALID;
        }

        $this->handleExecuteCommad($io, $title, $body);

        return Command::SUCCESS;
    }
}
