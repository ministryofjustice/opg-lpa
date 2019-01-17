<?php

namespace Application\Model\Service\Admin;

use Application\Model\Service\AbstractService;
use Application\Model\Service\ApiClient\ApiClientAwareInterface;
use Application\Model\Service\ApiClient\ApiClientTrait;
use Application\Model\Service\ApiClient\Exception\ApiException;
use DateTime;
use DateTimeZone;

class Admin extends AbstractService implements ApiClientAwareInterface
{
    use ApiClientTrait;

    /**
     * @param string $email
     * @return array|bool
     */
    public function searchUsers(string $email)
    {
        try {
            $result = $this->apiClient->httpGet('/v2/users/search', [
                'email' => $email
            ]);

            if ($result !== false) {
                $result = $this->parseDateTime($result, 'lastLoginAt');
                $result = $this->parseDateTime($result, 'updatedAt');
                $result = $this->parseDateTime($result, 'createdAt');
                $result = $this->parseDateTime($result, 'activatedAt');
                $result = $this->parseDateTime($result, 'deletedAt');

                if (array_key_exists('userId', $result) && $result['isActive'] === true) {
                    $numberOfLpas = 0;

                    try {
                        $applicationsResult = $this->apiClient->httpGet(
                            sprintf('/v2/user/%s/applications', $result['userId']),
                            [
                            'page' => 1,
                            'perPage' => 1,
                            ]
                        );

                        $numberOfLpas = $applicationsResult['total'];
                    } catch (ApiException $ignore) {
                    }

                    $result['numberOfLpas'] = $numberOfLpas;
                }
            }

            return $result;
        } catch (ApiException $ex) {
        }

        return false;
    }

    /**
     * @param array $result
     * @param string $key
     * @return array
     */
    private function parseDateTime(array $result, string $key)
    {
        if (array_key_exists($key, $result) && $result[$key] !== false) {
            $result[$key] = new DateTime($result[$key]['date'], new DateTimeZone($result[$key]['timezone']));
        }
        return $result;
    }
}
