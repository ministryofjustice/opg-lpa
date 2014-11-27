<?php
namespace Application\Model\Resources;


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

} // class
