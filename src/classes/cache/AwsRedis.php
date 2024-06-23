<?php

/**
 * This class require Redis server to be installed
 *
 * @since 1.0.0
 */
class AwsRedis extends CacheApi implements CacheApiInterface {

    /**
     * @var bool Connection status
     *
     * @since 1.0.0
     */
    public $is_connected = false;
    /**
     * @var RedisClient $redis
     *
     * @since 1.0.0
     */
    protected $redis;
    /**
     * @var array RedisParams
     *
     * @since 1.0.0
     */
    protected $_params = [];
    protected $_servers = [];

    /**
     * CacheRedisCore constructor.
     *
     * @since 1.0.0
     */
    public function __construct() {

        $this->connect();

        if ($this->is_connected) {
            $this->keys = $this->prefix;

            if (!is_array($this->keys)) {
                $this->keys = [];
            }

        }

    }

    public function connect() {

        $this->is_connected = false;
        $this->_servers = static::getRedisServers();

        if (!$this->_servers) {
            return;
        } else {

         
           $this->redis = new Redis();

           if ($this->redis->pconnect($this->_servers[0]['ip'], $this->_servers[0]['port'])) {
                
               $this->redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);

                    if (!empty($this->_servers[0]['auth'])) {

                        if (!($this->redis->auth($this->_servers[0]['auth']))) {
                            return;
                        } else {
                            $this->is_connected = true;
                        }

                    } else {
                        try {
                            $this->redis->select($this->_servers[0]['rdb']);
                            $ping = $this->redis->ping();

                            if (is_array($ping)) {
                                $ping = array_values($ping);

                                if (!empty($ping) && $ping[0] === '+PONG') {
                                    // We're connected if a connection without +AUTH receives a +PONG
                                    $this->is_connected = true;
                                }

                            }

                        } catch (Exception $e) {
                            $this->is_connected = false;
                        }

                    }

                }

            

        }

    }

    public static function getRedisServer() {

        $server = [];
        // bypass the memory fatal error caused functions nesting on PS 1.5
        $sql = new DbQuery();
        $sql->select('`name`, `value`');
        $sql->from('configuration');
        $sql->where('`name` = \'EPH_REDIS_SERVER\' OR `name` = \'EPH_REDIS_PORT\' OR name = \'EPH_REDIS_AUTH\' OR name = \'EPH_REDIS_DB\'');
        $sql->where('main = 1');
        $params = Db::getInstance(_EPH_USE_SQL_SLAVE_)->grtValue($sql, true, false);

        $server[$params['name']] = $params['value'];

        return $server;
    }
	
	public static function getLastRedisServer() {

        $server = [];
        // bypass the memory fatal error caused functions nesting on PS 1.5
        $sql = new DbQuery();
        $sql->select('`id_redis_serveur`');
        $sql->from('redis_servers');
		$sql->orderBy('`id_redis_serveur` DESC');
        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue($sql);

        
    }

    public static function addServer($ip, $port, $auth, $db) {

        $sql = new DbQuery();
        $sql->select('count(*)');
        $sql->from('redis_servers');
        $sql->where('`ip` = \'' . pSQL($ip) . '\'');
        $sql->where('`port` = ' . (int) $port);
        $sql->where('`auth` = \'' . pSQL($auth) . '\'');
        $sql->where('`rdb` = ' . (int) $db);

        if (Db::getInstance(_EPH_USE_SQL_SLAVE_)->getValue($sql, false)) {
            $context = Context::getContext();
            $context->controller->errors[] =
            Tools::displayError('Redis server has already been added');

            return false;
        }

        return Db::getInstance()->insert(
            'redis_servers',
            [
                'ip'   => pSQL($ip),
                'port' => (int) $port,
                'auth' => pSQL($auth),
                'rdb'   => (int) $db,
            ],
            false,
            false
        );
    }
    
    

    public static function getRedisServers() {

        $sql = new DbQuery();
        $sql->select('*');
        $sql->from('redis_servers');

        return Db::getInstance(_EPH_USE_SQL_SLAVE_)->executeS($sql, true, false);
    }
    
    public static function deleteServer($idServer) {

        return Db::getInstance()->delete(
            'redis_servers',
            '`id_redis_server` = ' . (int) $idServer,
            0,
            false
        );
    }

    public function __destruct() {

        $this->close();
    }

    protected function close() {

        if (!$this->is_connected) {
            return false;
        }

        // Don't close the connection, needs to be persistent across PHP-sessions
        return true;
    }
    
    public function cleanCache($type = '') {
        
        return (bool) $this->redis->flushDB();
    }

    public function flush() {

        return (bool) $this->redis->flushDB();
    }
        
    public function getKeys($prefix = null) {
        
        return $this->redis->keys($prefix.'*');
    }
    
    public function getRedisValues() {
        $result = [];
        $values = $this->redis->keys('*');
        if(is_array($values)) {
            foreach($values as $value) {
                $result[$value] = Tools::jsonDecode($this->redis->get($value), true);
            }
        }
        ksort($result);
        return $result;
    }
    
    public function putData($key, $value, $ttl = 3600) {

		return $this->_set($key, $value, $ttl);

	}

    protected function _set($key, $value, $ttl = 0) {

        return $this->redis->set($key, $value, $ttl);
    }
    
    public function keyExist($key) {

		return $this->_get($key);

	}
    
    public function isStored($key) {
        
        return $this->_exists($key);
    }

    protected function _exists($key) {

        return (bool) $this->_get($key);
    }
    
    public function getData($key, $ttl = null) {

		return $this->redis->get($key);
	}

    protected function _get($key) {

        return $this->redis->get($key);
    }
    
    public function removeData($key) {

		return $this->_delete($key);
	}
    
    public function getnbKeys() {
        
        return $this->redis->dbSize();
    }
    
    public function getRedisInfo() {
        
        return $this->redis->info();
    }


    protected function _delete($key) {

        return $this->redis->del($key);
    }
    
    
    protected function _writeKeys() {

        if (!$this->is_connected) {
            return false;
        }

        $this->redis->set($this->prefix, $this->keys);

        return true;
    }

}