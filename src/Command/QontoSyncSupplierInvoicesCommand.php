<?php

declare(strict_types=1);

namespace App\Command;

use App\Exceptions\InvoicingPlatformException;
use App\Repository\UserRepository;
use App\Service\InvoicingPlatform\SupplierInvoiceSyncService;
use DateTimeImmutable;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:qonto:sync-supplier-invoices',
    description: 'Importe les factures fournisseurs Qonto en factures produits (PDF), sans doublon',
)]
final class QontoSyncSupplierInvoicesCommand extends Command
{
    public function __construct(
        private readonly SupplierInvoiceSyncService $syncService,
        private readonly UserRepository $userRepository,
        #[Autowire('%env(QONTO_USER_EMAIL)%')]
        private readonly string $userEmail,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'from',
            null,
            InputOption::VALUE_REQUIRED,
            'Date de début (AAAA-MM-JJ) pour la première synchro ; sinon reprise depuis la dernière synchro réussie',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ('' === trim($this->userEmail)) {
            $io->error('QONTO_USER_EMAIL n\'est pas défini.');

            return Command::INVALID;
        }

        $user = $this->userRepository->findOneBy(['email' => $this->userEmail]);
        if (null === $user) {
            $io->error("Aucun utilisateur pour QONTO_USER_EMAIL « {$this->userEmail} ».");

            return Command::INVALID;
        }

        /** @var null|string $fromOption */
        $fromOption = $input->getOption('from');
        $from = null;
        if (null !== $fromOption) {
            $from = DateTimeImmutable::createFromFormat('!Y-m-d', $fromOption);
            if (false === $from || $from->format('Y-m-d') !== $fromOption) {
                $io->error("Option --from invalide « {$fromOption} » : format attendu AAAA-MM-JJ.");

                return Command::INVALID;
            }
        }

        try {
            $result = $this->syncService->sync($user, $from);
        } catch (InvoicingPlatformException $exception) {
            $io->error("Synchro interrompue : {$exception->getMessage()} La prochaine synchro reprendra au même point.");

            return Command::FAILURE;
        }

        $io->table(
            ['Créées', 'Mises à jour', 'Ignorées', 'Erreurs'],
            [[$result->created, $result->updated, $result->skipped, count($result->errors)]],
        );

        if ([] !== $result->unsupported) {
            $io->warning(['Pièces jointes au format non pris en charge, à ajouter à la main :', ...$result->unsupported]);
        }

        if ($result->hasErrors()) {
            $io->error(['Factures en erreur, retentées à la prochaine synchro :', ...$result->errors]);

            return Command::FAILURE;
        }

        $io->success('Synchro terminée jusqu\'au ' . $result->syncedUntil?->format('d/m/Y H:i:s') . '.');

        return Command::SUCCESS;
    }
}
