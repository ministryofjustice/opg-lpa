<?php

declare(strict_types=1);

namespace Application\Handler\Traits;

trait PaginationTrait
{
    /**
     * Get the pagination control data from the page settings provided
     *
     * @param int $page
     * @param int $lpasPerPage
     * @param int $lpasTotalCount
     * @param int $numberOfPagesInRange
     * @return array
     */
    private function getPaginationControlData(
        int $page,
        int $lpasPerPage,
        int $lpasTotalCount,
        int $numberOfPagesInRange
    ): array {
        // Determine the total number of pages
        $pageCount = (int) ceil($lpasTotalCount / $lpasPerPage);

        // If the requested page is higher than allowed then set it to the highest possible value
        if ($page > $pageCount) {
            $page = $pageCount;
        }

        // Figure out which pages to provide specific links to - pages in range
        // Start the pages in range array with the current page
        $pagesInRange = [$page];

        for ($i = 0; $i < ($numberOfPagesInRange - 1); $i++) {
            // Get the current lowest and highest page numbers
            $lowestPage = min($pagesInRange);
            $highestPage = max($pagesInRange);

            // If this is an even numbered iteration add a higher page number
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

        // Sort the page numbers into order
        asort($pagesInRange);

        // Figure out the first and last item number that are being displayed
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
