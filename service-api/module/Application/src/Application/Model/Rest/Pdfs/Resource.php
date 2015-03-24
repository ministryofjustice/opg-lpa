<?php
namespace Application\Model\Rest\Pdfs;

use RuntimeException;

use Credis_Client;

use Resque, Resque_Job_Status;

use Application\Library\Lpa\StateChecker;

use Application\Model\Rest\AbstractResource;

use Zend\Crypt\BlockCipher;
use Zend\Crypt\Symmetric\Exception\InvalidArgumentException as CryptInvalidArgumentException;

use Zend\Paginator\Adapter\Null as PaginatorNull;
use Zend\Paginator\Adapter\ArrayAdapter as PaginatorArrayAdapter;

use Application\Model\Rest\LpaConsumerInterface;
use Application\Model\Rest\UserConsumerInterface;

use Application\Library\Http\Response\File as FileResponse;

use Application\Library\ApiProblem\ApiProblem;
use Application\Library\ApiProblem\ValidationApiProblem;

class Resource extends AbstractResource implements UserConsumerInterface, LpaConsumerInterface {

    /**
     * Prefix for Redis keys of blobs (files).
     */
    const REDIS_FILE_PREFIX = 'pdf2:files:blob:';

    /**
     * Prefix for Redis keys of blobs (files).
     */
    const REDIS_TRACKING_PREFIX = 'pdf2:files:tracking:';

    //-----------

    /**
     * @var null|Credis_Client The redis client.
     */
    private $redis;

    //--------------------------

    public function getIdentifier(){ return 'resourceId'; }
    public function getName(){ return 'pdfs'; }

    public function getType(){
        return self::TYPE_COLLECTION;
    }

    public function getPdfTypes(){
        return ['lpa120', 'lp3', 'lp1'];
    }

    //----------------------------------------------------------------------

    /**
     * Get the Redis client.
     *
     * @return Credis_Client
     */
    protected function redis(){

        if( $this->redis instanceof Credis_Client ){
            return $this->redis;
        }

        $config = $this->getServiceLocator()->get('config')['db']['redis']['default'];

        $this->redis = new Credis_Client( $config['host'], $config['port'], $timeout = null, $persistent = '', $db = 1);

        return $this->redis;

    }

    //----------------------------------------------------------------------

    /**
     * Fetch a resource
     *
     * @param  mixed $id
     * @return Entity|ApiProblem
     * @throw UnauthorizedException If the current user is not authorized.
     */
    public function fetch($id){

        $this->checkAccess();

        //---

        $lpa = $this->getLpa();

        //---

        $validation = $lpa->validate();

        if( $validation->hasErrors() ){
            return new ValidationApiProblem( $validation );
        }

        //---

        if( !$lpa->isComplete() ){
            die('LPA not complete!');
        }

        //---

        if( in_array( $id, $this->getPdfTypes() ) ){

            $details = $this->getPdfDetails( $id );

            if( $details['status'] == Entity::STATUS_NOT_QUEUED ){
                // Then add the LPA to the PDF queue.

                $this->addLpaToQueue( $id );

                $details['status'] = Entity::STATUS_IN_QUEUE;

            }

            // Then assume they're asking for info about a PDF...
            return new Entity( $details, $lpa );

        }

        //--------------------------------------------------------
        // If they've added '.pdf' onto the end of the type...

        // They want to download the file.

        // Create array including '.pdf'...
        $typesWithExtention = array_map( function($v){
            return "{$v}.pdf";
        }, $this->getPdfTypes() );


        if( in_array( $id, $typesWithExtention ) ){

            // Then they're trying to download the PDF...

            $type = rtrim( $id, '.pdf' );

            $file = $this->getPdfFile( $type );

            if( $file !== false ){
                return new FileResponse( $file, FileResponse::TYPE_PDF );
            }

        } // if

        //---

        return new ApiProblem( 404, 'Document not found' );

    }

    /**
     * Fetch all or a subset of resources
     *
     * @param  array $params
     * @return Collection
     * @throw UnauthorizedException If the current user is not authorized.
     */
    public function fetchAll($params = array()){

        $this->checkAccess();

        //---

        $lpa = $this->getLpa();

        //---

        $data = array();

        foreach( $this->getPdfTypes() as $type) {

            $data[$type] = $this->getPdfDetails( $type );

        } // foreach

        //---

        $collection = new Collection( new PaginatorArrayAdapter( $data ), $lpa );

        // Always return all attorneys on one page.
        $collection->setItemCountPerPage( count($data) );

        //---

        return $collection;

    }

    //----------------------------------------------------------------------

    private function getPdfDetails( $type ){

        $lpa = $this->getLpa();

        //---

        // Check if we can generate this document type.

        $state = new StateChecker( $lpa );

        switch ($type){
            case 'lp1':
                $complete = $state->canGenerateLP1(); break;
            case 'lp3':
                $complete = $state->canGenerateLP3(); break;
            case 'lpa120':
                $complete = $state->canGenerateLPA120(); break;
            default:
                $complete = false;
        }

        //---

        // If the LPA is complete, we check to see the status...
        if( $complete ){

            $status = $this->getPdfStatus( $type );

        } else {

            // Otherwise the status is 'not available'...
            $status = Entity::STATUS_NOT_AVAILABLE;

        }

        //---

        return array(
            'type' => $type,
            'complete' => $complete,
            'status' => $status,
        );

    } // function

    /**
     * This checks the status of the PDF document that represents the LPA (and form type).
     *
     * The result can be:
     * - It's not in the queue (because it's not be requested to be)
     * - It's in the queue ready fro processing.
     * - It's ready to be downloaded.
     *
     * @param $type
     * @return string
     */
    private function getPdfStatus( $type ){

        $ident = $this->getPdfIdent( $type );

        //---

        // Check if the file already exists in teh cache.

        $exists = $this->redis()->exists( self::REDIS_FILE_PREFIX . $ident );

        // If the PDF is currently cached in redis...
        if( $exists ){

            // Clean up the tracking id as it's no longer useful...
            $this->redis()->del( self::REDIS_TRACKING_PREFIX . $ident );

            return Entity::STATUS_READY;
        }

        //---

        // Check if we have a resque tracking id for this file...
        $trackingId = $this->redis()->get( self::REDIS_TRACKING_PREFIX . $ident );

        if( !is_string($trackingId) ){
            return Entity::STATUS_NOT_QUEUED;
        }

        //---

        $config = $this->getServiceLocator()->get('config')['db']['resque']['default'];

        // Check if the PDF is in the queue...
        Resque::setBackend( "{$config['host']}:{$config['port']}" );

        //---

        $status = (new Resque_Job_Status( $trackingId ))->get();

        if( $status === Resque_Job_Status::STATUS_WAITING || $status === Resque_Job_Status::STATUS_RUNNING ){
            return Entity::STATUS_IN_QUEUE;
        }

        // We don't check for 'complete' as we do that above by checking if the file is in the cache.

        //---

        // Clean up the tracking id as it's no longer useful...
        $this->redis()->del( self::REDIS_TRACKING_PREFIX . $ident );

        // If we get here it's not in the queue...
        return Entity::STATUS_NOT_QUEUED;

    } // function

    private function addLpaToQueue( $type ){

        $lpa = $this->getLpa();

        $ident = $this->getPdfIdent( $type );

        //---

        $config = $this->getServiceLocator()->get('config')['db']['resque']['default'];

        // Check if the PDF is in the queue...
        Resque::setBackend( "{$config['host']}:{$config['port']}" );

        //---

        $config = $this->getServiceLocator()->get('config')['pdf']['encryption'];

        if( !is_string($config['keys']['queue']) || strlen($config['keys']['queue']) != 32 ){
            throw new CryptInvalidArgumentException('Invalid encryption key');
        }

        // We use AES encryption with Cipher-block chaining (CBC); via PHPs mcrypt extension
        $blockCipher = BlockCipher::factory('mcrypt', $config['options']);

        // Set the secret key
        $blockCipher->setKey( $config['keys']['queue'] );

        // Encrypt the JSON...
        $encryptedJson = $blockCipher->encrypt( $lpa->toJson( false ) );

        //---

        $trackingId = Resque::enqueue('pdf-queue', '\Opg\Lpa\Pdf\Worker\ResqueWorker', [
            'docId' => $ident,
            'type' => strtoupper($type),
            'lpa' => $encryptedJson
        ], true);

        //---

        // Store the tracking id in redis.
        $this->redis()->set( self::REDIS_TRACKING_PREFIX . $ident, $trackingId );

        // Expire the tracking after 24 hours.
        $this->redis()->expire( self::REDIS_TRACKING_PREFIX . $ident, ( 60 * 60 * 24 ) );

    } // function

    public function getPdfFile( $type ){

        $ident = $this->getPdfIdent( $type );

        //---

        $file = $this->redis()->get( self::REDIS_FILE_PREFIX . $ident );

        if( is_bool($file) ){
            return false;
        }

        //-------------------------------------
        // Decrypt the PDF

        $config = $this->getServiceLocator()->get('config')['pdf']['encryption'];

        if( !is_string($config['keys']['document']) || strlen($config['keys']['document']) != 32 ){
            throw new CryptInvalidArgumentException('Invalid encryption key');
        }

        // We use AES encryption with Cipher-block chaining (CBC); via PHPs mcrypt extension
        $blockCipher = BlockCipher::factory('mcrypt', $config['options']);

        // Set the secret key
        $blockCipher->setKey( $config['keys']['document'] );
        $blockCipher->setBinaryOutput( true );

        // Encrypt the JSON...
        $file = $blockCipher->decrypt( $file );

        //---

        return $file;

    } // function

    /**
     * Generates a unique identifier the represents the LPA data and type of form ( lp1, lp3, etc. ).
     *
     * @param $type
     * @return string
     */
    private function getPdfIdent( $type ){

        $lpa = $this->getLpa();

        $hash = md5( $lpa->toJson() );

        return strtolower( "{$type}-{$hash}" );

    } // function

} // class
