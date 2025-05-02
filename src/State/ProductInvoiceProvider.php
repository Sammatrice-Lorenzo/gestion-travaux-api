<?php

namespace App\State;

use DateTime;
use App\Entity\User;
use App\Entity\ProductInvoiceFile;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Symfony\Bundle\SecurityBundle\Security;
use App\Repository\ProductInvoiceFileRepository;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class ProductInvoiceProvider implements ProviderInterface
{
    public function __construct(
        private readonly ProductInvoiceFileRepository $productInvoiceFileRepository,
        private readonly Security $security,
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
     * @return ProductInvoiceFile[]
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        /** @var ?User $user */
        $user = $this->security->getUser();

        if (!$user) {
            throw new AccessDeniedHttpException();
        }

        $date = new DateTime();
        $contextDate = $context['filters']['date'];
        if ($contextDate) {
            $contextDate = is_array($contextDate) ? $contextDate[0] : $contextDate;
            $date = new DateTime($this->presetDate($contextDate));
        }

        return $this->productInvoiceFileRepository->findByMonth($user, $date);
    }
}
