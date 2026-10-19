<?php

declare(strict_types=1);

namespace App\Command;

use App\Dto\InvoicingPlatform\SupplierInvoiceSyncResult;
use App\Entity\User;
use App\Exceptions\InvoicingPlatformException;
use App\Exceptions\SupplierInvoiceSyncAlreadyRunningException;
use App\Repository\UserRepository;
use App\Service\InvoicingPlatform\SupplierInvoiceSyncService;
use DateTimeImmutable;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Scheduler\Attribute\AsCronTask;

// Toutes les heures, via le worker Scheduler ; un échec (code de sortie ≠ 0) remonte dans GlitchTip.
#[AsCronTask('0 * * * *', timezone: 'Europe/Paris')]
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

        try {
            $user = $this->resolveUser();
            $from = $this->parseFromOption($input);
        } catch (InvalidArgumentException $exception) {
            $io->error($exception->getMessage());

            return Command::INVALID;
        }

        return $this->synchronize($io, $user, $from);
    }

    /**
     * @throws InvalidArgumentException si QONTO_USER_EMAIL est vide ou ne correspond à aucun utilisateur
     */
    private function resolveUser(): User
    {
        if ('' === trim($this->userEmail)) {
            throw new InvalidArgumentException('QONTO_USER_EMAIL n\'est pas défini.');
        }

        return $this->userRepository->findOneBy(['email' => $this->userEmail])
            ?? throw new InvalidArgumentException("Aucun utilisateur pour QONTO_USER_EMAIL « {$this->userEmail} ».");
    }

    /**
     * @throws InvalidArgumentException si --from n'est pas une date AAAA-MM-JJ valide
     */
    private function parseFromOption(InputInterface $input): ?DateTimeImmutable
    {
        /** @var null|string $fromOption */
        $fromOption = $input->getOption('from');
        if (null === $fromOption) {
            return null;
        }

        $from = DateTimeImmutable::createFromFormat('!Y-m-d', $fromOption);
        if (false === $from || $from->format('Y-m-d') !== $fromOption) {
            throw new InvalidArgumentException("Option --from invalide « {$fromOption} » : format attendu AAAA-MM-JJ.");
        }

        return $from;
    }

    private function synchronize(SymfonyStyle $io, User $user, ?DateTimeImmutable $from): int
    {
        try {
            $result = $this->syncService->sync($user, $from);
        } catch (SupplierInvoiceSyncAlreadyRunningException $exception) {
            // Échec volontaire : cette exécution n'a rien synchronisé, le heartbeat ne doit pas être envoyé.
            $io->warning($exception->getMessage());

            return Command::FAILURE;
        } catch (InvoicingPlatformException $exception) {
            $io->error("Synchro interrompue : {$exception->getMessage()} La prochaine synchro reprendra au même point.");

            return Command::FAILURE;
        }

        $this->renderReport($io, $result);

        return $result->isSuccessful() ? Command::SUCCESS : Command::FAILURE;
    }

    private function renderReport(SymfonyStyle $io, SupplierInvoiceSyncResult $result): void
    {
        $io->table(
            ['Créées', 'Mises à jour', 'Ignorées', 'Erreurs', 'Échecs'],
            [[$result->created, $result->updated, $result->skipped, count($result->errors), count($result->failures)]],
        );

        if ([] !== $result->unsupported) {
            $io->warning(['Factures au format ou dans une devise non pris en charge, à ajouter à la main :', ...$result->unsupported]);
        }

        if ($result->hasFailures()) {
            $io->error(['Factures impossibles à importer, ignorées jusqu\'à leur prochaine modification sur la plateforme (ou relancer avec --from) :', ...$result->failures]);
        }

        if ($result->hasErrors()) {
            $io->error(['Factures en erreur, retentées à la prochaine synchro :', ...$result->errors]);
        }

        if ($result->isSuccessful()) {
            $io->success('Synchro terminée jusqu\'au ' . $result->syncedUntil?->format('d/m/Y H:i:s') . '.');
        }
    }
}
