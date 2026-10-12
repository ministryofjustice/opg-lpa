<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\FormFlowChecker;
use App\Service\Lpa\Application as LpaApplicationService;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use MakeShared\DataModel\Lpa\Document\Correspondence;
use MakeShared\DataModel\Lpa\Lpa;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Mezzio\Helper\UrlHelper;

class CorrespondenceSetService
{
    public function __construct(
        private readonly LpaApplicationService $lpaApplicationService,
        private readonly UrlHelper $urlHelper,
    ) {
    }

    public function setCorrespondent(
        Lpa $lpa,
        array $correspondentData,
        FormFlowChecker $flowChecker,
        bool $isPopup,
        int $ifMatchVersion,
    ): ResponseInterface {
        $lpaCorrespondent = $lpa->document->correspondent;

        // Set aside any data to retain that is not present in the form
        $existingDataToRetain = [];

        if ($lpaCorrespondent instanceof Correspondence) {
            $existingDataToRetain = [
                'contactByPost'  => $lpaCorrespondent->contactByPost,
                'contactInWelsh' => $lpaCorrespondent->contactInWelsh,
            ];
        }

        $lpaCorrespondent = new Correspondence(array_merge($correspondentData, $existingDataToRetain));

        if (!$this->lpaApplicationService->setCorrespondent($lpa, $lpaCorrespondent, $ifMatchVersion)) {
            throw new RuntimeException('API client failed to update correspondent for id: ' . $lpa->id);
        }

        if ($isPopup) {
            return new JsonResponse(['success' => true]);
        }

        $nextRoute = $flowChecker->nextRoute('lpa/correspondent/edit');

        return new RedirectResponse(
            $this->urlHelper->generate(
                $nextRoute,
                ['lpa-id' => $lpa->id],
                $flowChecker->getRouteOptions($nextRoute)
            )
        );
    }
}
