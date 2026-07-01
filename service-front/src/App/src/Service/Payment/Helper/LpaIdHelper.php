<?php

declare(strict_types=1);

namespace App\Service\Payment\Helper;

class LpaIdHelper
{
    public const LPA_ID_LENGTH = 11;

    /**
     * Helper function to construct the transaction ID
     * based on the LPA ID
     *
     * @param string $lpaId
     * @return string
     */
    public static function constructPaymentTransactionId(string $lpaId): string
    {
        return self::padLpaId($lpaId);
    }

    public static function padLpaId(string $lpaId): string
    {
        if (strlen($lpaId) > self::LPA_ID_LENGTH) {
            throw new \Exception('LPA ID is too long');
        }

        return str_pad($lpaId, self::LPA_ID_LENGTH, '0', STR_PAD_LEFT);
    }
}
