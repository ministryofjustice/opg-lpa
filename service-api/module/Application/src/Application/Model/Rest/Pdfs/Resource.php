<?php

namespace Application\Model\Rest\Pdfs;

use Application\Library\ApiProblem\ApiProblem;
use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Library\Http\Response\File as FileResponse;
use Application\Library\Lpa\StateChecker;
use Application\Model\Rest\AbstractResource;
use Application\Model\Rest\LpaConsumerInterface;
use Application\Model\Rest\UserConsumerInterface;
use Aws\S3\S3Client;
use DynamoQueue\Queue\Client as DynamoQueue;
use DynamoQueue\Queue\Job\Job as DynamoQueueJob;
use Zend\Crypt\BlockCipher;
use Zend\Crypt\Symmetric\Exception\InvalidArgumentException as CryptInvalidArgumentException;
use Zend\Filter\Compress;
use Zend\Paginator\Adapter\ArrayAdapter as PaginatorArrayAdapter;

class Resource extends AbstractResource implements UserConsumerInterface, LpaConsumerInterface
{
    /**
     * Resource name
     *
     * @var string
     */
    protected $name = 'pdfs';

    /**
     * Resource identifier
     *
     * @var string
     */
    protected $identifier = 'resourceId';

    /**
     * Resource type
     *
     * @var string
     */
    protected $type = self::TYPE_COLLECTION;

    /**
     * The compression adapter to use (with ZF2 Filters)
     * We compress JSON put into the queue with this.
     */
    const COMPRESSION_ADAPTER = 'Gz';

    //-----------

    /**
     * Used for the PDF cache.
     * @var S3Client
     */
    private $s3Client;

    /**
     * Used for the PDF queue.
     * @var DynamoQueue
     */
    private $dynamoQueue;

    //--------------------------

    public function getPdfTypes(){
        return ['lpa120', 'lp3', 'lp1'];
    }

    //----------------------------------------------------------------------


    /**
     * Returns a configured instance of the AWS S3 client.
     *
     * @return S3Client
     */
    protected function getS3Client(){

        if( $this->s3Client instanceof S3Client ){
            return $this->s3Client;
        }

        //---

        $config = $this->getServiceLocator()->get('config')['pdf']['cache']['s3']['client'];

        $this->s3Client = new S3Client( $config );

        return $this->s3Client;

    }

    protected function getDynamoQueueClient(){

        if( $this->dynamoQueue instanceof DynamoQueue ){
            return $this->dynamoQueue;
        }

        //---

        $this->dynamoQueue = $this->getServiceLocator()->get('DynamoQueueClient');

        return $this->dynamoQueue;

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
     * - It's not in the queue (because it has not been added)
     * - It's in the queue ready for processing.
     * - It's ready to be downloaded.
     *
     * @param $type
     * @return string
     */
    private function getPdfStatus( $type ){

        $ident = $this->getPdfIdent( $type );

        //-------------------------------------------------
        // Check if the file already exists in the cache.

        $bucketConfig = $this->getServiceLocator()->get('config')['pdf']['cache']['s3']['settings'];

        try {

            $this->getS3Client()->headObject($bucketConfig + [
                    'Key' => $ident,
            ]);

            // If it's in the cache, clean it out of the queue.
            $this->getDynamoQueueClient()->deleteJob( $ident );

            // If we get here it exists in the bucket...
            return Entity::STATUS_READY;

        } catch( \Aws\S3\Exception\S3Exception $e ){
            // If it doesn't exist in the bucket we get this exception.
            // We just carry on below.
        }


        //-------------------------------------------------
        // Check for the job in the queue

        // Get the job's status in the queue.
        $status = $this->getDynamoQueueClient()->checkStatus( $ident );


        if( in_array( $status, [ DynamoQueueJob::STATE_WAITING, DynamoQueueJob::STATE_PROCESSING ] ) ){
            // Then the job is in the queue...
            return Entity::STATUS_IN_QUEUE;
        }

        if( in_array( $status, [ DynamoQueueJob::STATE_DONE, DynamoQueueJob::STATE_ERROR ] ) ){

            // If we get here something strange has happened because:
            //  - The PDF should have been in the cache; or
            //  - An error occurred.

            // For now we just remove the job from teh queue so it can be re-added.
            $this->getDynamoQueueClient()->deleteJob( $ident );

        }

        // else it's not in the queue.
        return Entity::STATUS_NOT_QUEUED;

    } // function

    private function addLpaToQueue( $type ){

        $lpa = $this->getLpa();

        $ident = $this->getPdfIdent( $type );

        //----------------------
        // Setup the message

        $message = json_encode([
            'lpa' => $lpa->toArray(),
            'type' => strtoupper($type), // The type of document we want generating
        ]);

        // Compress the message.
        $message = (new Compress( self::COMPRESSION_ADAPTER ))->filter( $message );

        //----------------------
        // Encrypt the message

        $config = $this->getServiceLocator()->get('config')['pdf']['encryption'];

        if( !is_string($config['keys']['queue']) || strlen($config['keys']['queue']) != 32 ){
            throw new CryptInvalidArgumentException('Invalid encryption key');
        }

        // We use AES encryption with Cipher-block chaining (CBC); via PHPs mcrypt extension
        $blockCipher = BlockCipher::factory('mcrypt', $config['options']);

        // Set the secret key
        $blockCipher->setKey( $config['keys']['queue'] );

        // Encrypt the JSON...
        $encryptedMessage = $blockCipher->encrypt( $message );

        //----------------------

        // Add the message to the queue.
        $this->getDynamoQueueClient()->enqueue( '\Opg\Lpa\Pdf\Worker\DynamoQueueWorker', $encryptedMessage, $ident );

    } // function

    public function getPdfFile( $type ){

        $ident = $this->getPdfIdent( $type );

        //---

        $bucketConfig = $this->getServiceLocator()->get('config')['pdf']['cache']['s3']['settings'];

        try {

            $file = $this->getS3Client()->getObject($bucketConfig + [
                    'Key' => $ident,
            ]);


        } catch( \Aws\S3\Exception\S3Exception $e ){

            return false;

        }

        //---

        $file = $file['Body']->getContents();

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

        $keys = $this->getServiceLocator()->get('config')['pdf']['encryption']['keys'];

        $lpa = $this->getLpa();

        // $keys are included so a new ident is generated when encryption keys change.
        $hash = hash( 'sha512', md5( $lpa->toJson() ) . $keys['document'] . $keys['queue'] );

        return strtolower( "{$type}-{$hash}" );

    } // function

} // class
