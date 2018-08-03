<?php

namespace Application\Model\Service\Users;

use Application\Model\DataAccess\Mongo\Collection\ApiUserCollection;
use Application\Model\DataAccess\Mongo\DateCallback;
use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Library\DateTime;
use Application\Model\Service\AbstractService;
use Application\Model\Service\Applications\Service as ApplicationService;
use Application\Model\Service\DataModelEntity;
use Auth\Model\Service\UserManagementService;
use MongoDB\BSON\UTCDateTime;
use Opg\Lpa\DataModel\User\User;

class Service extends AbstractService
{
    /**
     * @var ApiUserCollection
     */
    private $apiUserCollection;

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
        $user = $this->apiUserCollection->findOne([
            '_id' => $id
        ]);

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
        $this->apiUserCollection->deleteOne(['_id' => $id]);

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
        $user = $this->apiUserCollection->findOne([
            '_id' => $id
        ]);

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
            $this->apiUserCollection->insertOne($user->toArray(new DateCallback()));
        } else {
            $validation = $user->validate();

            if ($validation->hasErrors()) {
                return new ValidationApiProblem($validation);
            }

            $lastUpdated = new UTCDateTime($user->updatedAt);

            // Record the time we updated the user.
            $user->updatedAt = new DateTime();

            // updatedAt is included in the query so that data isn't overwritten
            // if the User has changed since this process loaded it.
            $result = $this->apiUserCollection->updateOne(
                ['_id' => $user->id, 'updatedAt' => $lastUpdated],
                ['$set' => $user->toArray(new DateCallback())],
                ['upsert' => false, 'multiple' => false]
            );

            // Ensure that one (and only one) document was updated.
            // If not, something when wrong.
            if ($result->getModifiedCount() !== 0 && $result->getModifiedCount() !== 1) {
                throw new \RuntimeException('Unable to update User. This might be because "updatedAt" has changed.');
            }
        }

        return $user;
    }

    /**
     * @param ApiUserCollection $apiUserCollection
     */
    public function setApiUserCollection(ApiUserCollection $apiUserCollection)
    {
        $this->apiUserCollection = $apiUserCollection;
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
