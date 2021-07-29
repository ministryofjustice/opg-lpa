<?php
namespace Application\Model\Service\Session;

use Application\Logging\LoggerTrait;
use Laminas\Session\SaveHandler\SaveHandlerInterface;


/**
 * Custom save handler to which write filters can be applied.
 * If any filter in the chain returns FALSE, the session
 * will not be written.
 * Typically, filters are closures which inspect the incoming
 * request, environment etc. to determine whether the session
 * should be written after execution of an action.
 */
class FilteringSaveHandler implements SaveHandlerInterface
{
    use LoggerTrait;

    private $filters = [];
    private $savePath;

    /**
     * Add a filter to the chain. Filters in the chain
     * are checked in the order they were added.
     *
     * @param callable $closure Closure which returns TRUE
     * (session should be written) or FALSE (ignore the write
     * for this session)
     */
    public function addFilter(callable $closure)
    {
        $this->filters[] = $closure;
        return $this;
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
        $this->getLogger()->debug(
            sprintf('XXXXXXXXXXXXXXXXXXXXXXXXXXXXXX Reading session data at %s; session data = %s',
                microtime(TRUE), serialize($data))
        );
        return $data;
    }

    public function write($id, $data)
    {
        // Ignore writes if any filter returns FALSE
        $doWrite = TRUE;
        foreach ($this->filters as $_ => $filter) {
            if (!$filter()) {
                $doWrite = FALSE;
                break;
            }
        }

        if ($doWrite) {
            $this->getLogger()->debug(sprintf('XXXXXXXXXXXXXXXXXXXXXXXXXXXXXX Writing data to session at %s; session data = %s', microtime(TRUE), serialize($data)));
            return file_put_contents("$this->savePath/sess_$id", $data) === false ? false : true;
        }
        else {
            $this->getLogger()->debug(sprintf('XXXXXXXXXXXXXXXXXXXXXXXXXXXXXX Ignoring session write at %s for request', microtime(TRUE)));
            return TRUE;
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