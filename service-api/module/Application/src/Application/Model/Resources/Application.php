<?php
namespace Application\Model\Resources;

use Hateoas\Configuration\Metadata\ClassMetadataInterface;
use Hateoas\Configuration as Hateoas;

/**
 * Class Application
 * @package Application\Model\Resources
 * @Hateoas\Annotation\RelationProvider(name = "addRelations")
 */
class Application {

    private $id = 123;
    private $name = 'Bob';
    private $password ='xxxxxxxxxx';


    public function getId() {
        return $this->id;
    }

    public function addRelations($object, ClassMetadataInterface $classMetadata){

        // You need to return the relations
        // Adding the relations to the $classMetadata won't work
        return array(
            new Hateoas\Relation(
                'self',
                "expr('/api/users/' ~ object.getId())"
            )
        );

    } // function


} // class
