<?php

namespace Application\Model\Service\Admin;

use Application\Model\Service\AbstractService;
use Application\Model\Service\ApiClient\ApiClientAwareInterface;
use Application\Model\Service\ApiClient\ApiClientTrait;
use Application\Model\Service\ApiClient\Exception\ResponseException;
use Application\Model\Service\AuthClient\AuthClientAwareInterface;
use Application\Model\Service\AuthClient\AuthClientTrait;
use DateTime;
use DateTimeZone;
use Exception;

class Admin extends AbstractService implements ApiClientAwareInterface, AuthClientAwareInterface
{
    use ApiClientTrait;
    use AuthClientTrait;

    /**
     * @param string $email
     * @return array|bool
     */
    public function searchUsers(string $email)
    {
        $response = $this->authClient->httpGet('/v1/users/search', ['email' => $email]);

        if ($response->getStatusCode() == 200) {
            $result = json_decode($response->getBody(), true);

            if ($result !== false) {
                $result = $this->parseDateTime($result, 'lastLoginAt');
                $result = $this->parseDateTime($result, 'updatedAt');
                $result = $this->parseDateTime($result, 'createdAt');
                $result = $this->parseDateTime($result, 'activatedAt');
                $result = $this->parseDateTime($result, 'deletedAt');

                if (array_key_exists('userId', $result) && $result['isActive'] === true) {
                    $numberOfLpas = 0;

                    try {
                        $response = $this->apiClient->httpGet(sprintf('/v2/users/%s/applications', $result['userId']), [
                            'page' => 1,
                            'perPage' => 1,
                        ]);

                        if ($response->getStatusCode() == 200) {
                            $body = json_decode($response->getBody(), true);
                            $numberOfLpas = $body['total'];
                        }
                    } catch (Exception $ignore) {}

                    $result['numberOfLpas'] = $numberOfLpas;
                }

                return $result;
            }
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