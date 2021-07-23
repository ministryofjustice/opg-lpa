<?php
namespace Application\Model\Service\Session;

use Redis;


/**
 * Client for Redis which saves a single CSRF-<session ID> key to Redis,
 * whose value is the current CSRF token for the user's session.
 * TODO TTL for tokens
 */
class CsrfClient {
    /**
     * Constructor
     * TODO extract port and pass to `new Redis()`
     * @param $redisUrl In format tcp://host:port (TODO could have trailing slash)
     */
    public function __construct($redisUrl, $cookieName, Redis $redis = null) {
        $redisUrl = str_replace('tcp://', '', $redisUrl);
        $redisUrl = preg_replace('/\:\d+/', '', $redisUrl);
        $this->redisUrl = $redisUrl;

        // Redis key we'll store the CSRF token against
        $this->key = 'CSRF-' . $cookieName;

        if (is_null($redis)) {
            $redis = new Redis();
        }
        $this->redis = $redis;
    }

    public function set(string $value) : bool {
        $this->redis->connect($this->redisUrl);
        $setOk = $this->redis->set($this->key, $value);
        $this->redis->close();
        return $setOk;
    }

    public function get() : string
    {
        $this->redis->connect($this->redisUrl);
        $value = $this->redis->get($this->key);
        $this->redis->close();
        return $value;
    }
}