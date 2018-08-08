<?php

namespace Application\Model\Service\Users;

use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Library\DateTime;
use Application\Model\DataAccess\Mongo\Collection\ApiUserCollectionTrait;
use Application\Model\Service\AbstractService;
use Application\Model\Service\Applications\Service as ApplicationService;
use Application\Model\Service\DataModelEntity;
use Application\Model\Service\UserManagement\Service as UserManagementService;
use Opg\Lpa\DataModel\User\User;

class Service extends AbstractService
{
    use ApiUserCollectionTrait;

    /**
     * @var ApplicationService
     */
    private $applicationsService;

    /**
     * @var UserManagementService $userManagementService
     */
    private $userManagementService;

    /**
     * @param $id
     * @return ValidationApiProblem|DataModelEntity|array|null|object|User
     */
    public function fetch($id)
    {
        //  Try to get an existing user
        $user = $this->apiUserCollection->getById($id);

        //  If there is no user create one now and ensure that the email address is correct

        if (is_null($user)) {
            $user = $this->save($id);
        } else {
            //  Create the user object using the data
            $user = new User([
                'id' => $id
            ] + $user);
        }

        return new DataModelEntity($user);
    }

    /**
     * @param $data
     * @param $id
     * @return ValidationApiProblem|DataModelEntity|array|null|object|User
     */
    public function update($data, $id)
    {
        $user = $this->save($id, $data);

        // If it's not a user, it's a different kind of response, so return it.
        if (!$user instanceof User) {
            return $user;
        }

        return new DataModelEntity($user);
    }

    /**
     * @param $id
     * @return bool
     */
    public function delete($id)
    {
        // Delete all applications for the user.
        $this->applicationsService->deleteAll($id);

        // Delete the user's About Me details.
        $this->apiUserCollection->deleteById($id);

        $this->userManagementService->delete($id, 'user-initiated');

        return true;
    }

    /**
     * @param $id
     * @param array $data
     * @return ValidationApiProblem|array|null|object|User
     */
    private function save($id, array $data = [])
    {
        $user = $this->apiUserCollection->getById($id);

        // Protect these values from the client setting them manually.
        unset($data['id'], $data['email'], $data['createdAt'], $data['updatedAt']);

        $new = false;

        if (is_null($user)) {
            $user = [
                'id'        => $id,
                'createdAt' => new DateTime(),
                'updatedAt' => new DateTime(),
            ];

            $new = true;
        } else {
            $user = [
                'id' => $user['_id']
            ] + $user;
        }

        //  Keep email up to date with what's in the auth service
        $authUserData = $this->userManagementService->get($id);
        $data['email']['address'] = $authUserData['username'];

        $data = array_merge($user, $data);

        $user = new User($data);

        if ($new) {
            $this->apiUserCollection->insert($user);
        } else {
            $validation = $user->validate();

            if ($validation->hasErrors()) {
                return new ValidationApiProblem($validation);
            }

            $this->apiUserCollection->update($user);
        }

        return $user;
    }

    /**
     * @param ApplicationService $applicationsService
     */
    public function setApplicationsService(ApplicationService $applicationsService)
    {
        $this->applicationsService = $applicationsService;
    }

    /**
     * @param UserManagementService $userManagementService
     */
    public function setUserManagementService(UserManagementService $userManagementService)
    {
        $this->userManagementService = $userManagementService;
    }
}
