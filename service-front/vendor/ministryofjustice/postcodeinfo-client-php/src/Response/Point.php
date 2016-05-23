<?php
namespace MinistryOfJustice\PostcodeInfo\Response;

class Point extends AbstractData {

    public function getLatitude(){
        return $this->coordinates[1];
    }

    public function getLongitude(){
        return $this->coordinates[0];
    }

}
