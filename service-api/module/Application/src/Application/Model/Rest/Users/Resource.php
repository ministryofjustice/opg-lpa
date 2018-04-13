<?php

namespace Application\Model\Rest\Users;

use Application\DataAccess\Mongo\DateCallback;
use Application\DataAccess\UserDal;
use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Library\Authentication\Identity\User as UserIdentity;
use Application\Library\DateTime;
use Application\Model\Rest\AbstractResource;
use Application\Model\Rest\Applications\Resource as ApplicationResource;
use MongoDB\BSON\UTCDateTime;
use Opg\Lpa\DataModel\User\User;

class Resource extends AbstractResource
{
    /**
     * @var UserDal
     */
    private $userDal;

    /**
     * @var ApplicationResource
     */
    private $applicationsResource;

    /**
     * @param $id
     * @return ValidationApiProblem|Entity|array|null|object|User
     */
    public function fetch($id)
    {
        $this->checkAccess($id);

        //  Get user using the DAL
        $user = $this->userDal->findById($id);

        //  If there is no user create one now and ensure that the email address is correct
        if (is_null($user)) {
            $user = $this->save($id);
            $this->userDal->injectEmailAddressFromIdentity($user);
        }

        return new Entity($user);
    }

    /**
     * @param $data
     * @param $id
     * @return ValidationApiProblem|Entity|array|null|object|User
     */
    public function update($data, $id)
    {
        $this->checkAccess($id);

        $user = $this->save($id, $data);

        // If it's not a user, it's a different kind of response, so return it.
        if (!$user instanceof User) {
            return $user;
        }

        return new Entity($user);
    }

    /**
     * @param $id
     * @return bool
     */
    public function delete($id)
    {
        $this->checkAccess($id);

        // Delete all applications for the user.
        $this->applicationsResource->deleteAll();

        // Delete the user's About Me details.
        $this->collection->deleteOne(['_id' => $id]);

        return true;
    }

    /**
     * @param $id
     * @param null $data
     * @return ValidationApiProblem|array|null|object|User
     */
    private function save($id, $data = null)
    {
        $this->checkAccess($id);

        $user = $this->collection->findOne(['_id' => $id]);

        // Ensure $data is an array.
        if (!is_array($data)) {
            $data = [];
        }

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
            $user = [ 'id' => $user['_id'] ] + $user;
        }

        $data = array_merge($user, $data);

        $user = new User($data);

        // Keep email up to date with what's in the authentication service.
        $identity = $this->getAuthorizationService()->getIdentity();

        if ($identity instanceof UserIdentity) {
            $user->email = [ 'address' => $identity->email() ];
        }

        if ($new) {
            $this->collection->insertOne($user->toArray(new DateCallback()));
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
            $result = $this->collection->updateOne(
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
     * @param UserDal $userDal
     */
    public function setUserDal(UserDal $userDal)
    {
        $this->userDal = $userDal;
    }

    /**
     * @param ApplicationResource $applicationsResource
     */
    public function setApplicationsResource(ApplicationResource $applicationsResource)
    {
        $this->applicationsResource = $applicationsResource;
    }
}
