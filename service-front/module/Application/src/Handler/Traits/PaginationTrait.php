<?php

declare(strict_types=1);

namespace Application\Handler\Traits;

trait PaginationTrait
{
    private function getPaginationControlData(
        int $page,
        int $lpasPerPage,
        int $lpasTotalCount,
        int $numberOfPagesInRange
    ): array {
        $pageCount = (int) ceil($lpasTotalCount / $lpasPerPage);

        if ($page > $pageCount) {
            $page = $pageCount;
        }

        $pagesInRange = [$page];

        for ($i = 0; $i < ($numberOfPagesInRange - 1); $i++) {
            $lowestPage = min($pagesInRange);
            $highestPage = max($pagesInRange);

            if ($i % 2 == 0) {
                if ($highestPage < $pageCount) {
                    $pagesInRange[] = ++$highestPage;
                } elseif ($lowestPage > 1) {
                    $pagesInRange[] = --$lowestPage;
                }
            } else {
                if ($lowestPage > 1) {
                    $pagesInRange[] = --$lowestPage;
                } elseif ($highestPage < $pageCount) {
                    $pagesInRange[] = ++$highestPage;
                }
            }
        }

        asort($pagesInRange);

        $firstItemNumber = (($page - 1) * $lpasPerPage) + 1;
        $lastItemNumber = min($page * $lpasPerPage, $lpasTotalCount);

        return [
            'page'            => $page,
            'pageCount'       => $pageCount,
            'pagesInRange'    => $pagesInRange,
            'firstItemNumber' => $firstItemNumber,
            'lastItemNumber'  => $lastItemNumber,
            'totalItemCount'  => $lpasTotalCount,
        ];
    }
}
