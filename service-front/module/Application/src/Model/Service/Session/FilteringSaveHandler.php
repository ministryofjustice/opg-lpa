<?php
namespace Application\Model\Service\Session;

use Application\Logging\LoggerTrait;
use Laminas\Session\SaveHandler\SaveHandlerInterface;


class FilteringSaveHandler implements SaveHandlerInterface
{
    use LoggerTrait;

    private $request;
    private $savePath;

    public function __construct($request = null)
    {
        $this->setRequest($request);
    }

    public function setRequest($request)
    {
        $this->request = $request;
    }

    // $savePath and $sessionName are ignored as we inject the $redisClient
    // into the save handler
    public function open($savePath, $sessionName): bool
    {
        $this->savePath = $savePath;
        if (!is_dir($this->savePath)) {
            mkdir($this->savePath, 0777);
        }

        return true;
    }

    public function close()
    {
        return true;
    }

    public function read($id)
    {
        $data = (string)@file_get_contents("$this->savePath/sess_$id");
        $this->getLogger()->debug(sprintf('XXXXXXXXXXXXXXXXXXXXXXXXXXXXXX Reading session data at %s for request on path %s; session data = %s', microtime(TRUE), $this->request->getUri()->getPath(), serialize($data)));
        return $data;
    }

    public function write($id, $data)
    {
        // Ignore writes initiated from an Ajax request
        if ($this->request->isXmlHttpRequest()) {
            $this->getLogger()->debug(sprintf('XXXXXXXXXXXXXXXXXXXXXXXXXXXXXX Ignoring session write at %s for Ajax request on path %s', microtime(TRUE), $this->request->getUri()->getPath()));
            return TRUE;
        }
        else {
            $this->getLogger()->debug(sprintf('XXXXXXXXXXXXXXXXXXXXXXXXXXXXXX Writing data to session at %s for request on path %s; session data = %s', microtime(TRUE), $this->request->getUri()->getPath(), serialize($data)));
            return file_put_contents("$this->savePath/sess_$id", $data) === false ? false : true;
        }
    }

    public function destroy($id)
    {
        $file = "$this->savePath/sess_$id";
        if (file_exists($file)) {
            unlink($file);
        }

        return true;
    }

    public function gc($maxlifetime)
    {
        foreach (glob("$this->savePath/sess_*") as $file) {
            if (filemtime($file) + $maxlifetime < time() && file_exists($file)) {
                unlink($file);
            }
        }

        return true;
    }
}