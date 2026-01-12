<?php

declare(strict_types=1);

namespace Application\View;

use DateTimeInterface;
use MakeShared\DataModel\Lpa\Lpa;

final class StatusViewData
{
    public function __construct(
        public readonly Lpa $lpa,
        public readonly ?DateTimeInterface $shouldReceiveByDate,
        public readonly bool $returnUnpaid,
        public readonly string $status,
        /** @var list<string> */
        public readonly array $doneStatuses,
        public readonly bool $canGenerateLPA120,
    ) {
    }

    public function toArray(): array
    {
        return [
            'lpa'                 => $this->lpa,
            'shouldReceiveByDate' => $this->shouldReceiveByDate,
            'returnUnpaid'        => $this->returnUnpaid,
            'status'              => $this->status,
            'doneStatuses'        => $this->doneStatuses,
            'canGenerateLPA120'   => $this->canGenerateLPA120,
        ];
    }
}
