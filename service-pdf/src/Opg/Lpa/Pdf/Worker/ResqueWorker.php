<?php

namespace Opg\Lpa\Pdf\Worker;

class ResqueWorker extends Worker {

    public function perform(){

        if( !isset($this->args['docId']) ){
            throw new \Exception('Missing field: docId');
        }

        if( !isset($this->args['lpa']) ){
            throw new \Exception('Missing field: lpa');
        }

        if( !isset($this->args['type']) ){
            throw new \Exception('Missing field: type');
        }

        echo "Running job\n";

        $this->run( $this->args['docId'], $this->args['type'], $this->args['lpa'] );

    } // function

} // class
