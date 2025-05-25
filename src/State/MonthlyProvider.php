<?php

namespace App\State;

use DateTime;
use App\Entity\User;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Interface\MonthlyProviderInterface;
use Symfony\Bundle\SecurityBundle\Security;
use App\Interface\MonthlyProviderRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @implements ProviderInterface<MonthlyProviderInterface>
 */
final class MonthlyProvider implements ProviderInterface
{
    private const string SEARCH_KEY_DATE = 'date';

    private const string SEARCH_KEY_START_DATE = 'startDate';

    public function __construct(
        private readonly Security $security,
        private readonly EntityManagerInterface $entityManagerInterface
    ) {}

    private function presetDate(string $date): string
    {
        $string = str_replace(search: '"', replace: '', subject: $date);
        $string = explode(',', $string)[0];

        return trim(str_replace(search: "'", replace: '', subject: $string));
    }

    /**
     * @throws AccessDeniedHttpException
     *
     * @return MonthlyProviderInterface[]
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        /** @var ?User $user */
        $user = $this->security->getUser();

        if (!$user) {
            throw new AccessDeniedHttpException();
        }

        /** @var class-string<MonthlyProviderInterface> */
        $entity = $context['resource_class'];
        /** @var MonthlyProviderRepositoryInterface $repository */
        $repository = $this->entityManagerInterface->getRepository($entity);

        $date = new DateTime();
        $filters = $context['filters'] ?? [];
        $isStartDateKey = array_key_exists(self::SEARCH_KEY_START_DATE, $filters);

        if ($isStartDateKey || array_key_exists(self::SEARCH_KEY_DATE, $filters)) {
            $contextDate = $filters[$isStartDateKey ? self::SEARCH_KEY_START_DATE : self::SEARCH_KEY_DATE];
            $contextDate = is_array($contextDate) ? $contextDate[0] : $contextDate;
            $date = new DateTime($this->presetDate($contextDate));
        }

        return $repository->findByMonth($user, $date);
    }
}
