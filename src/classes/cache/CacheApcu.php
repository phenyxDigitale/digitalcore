<?php

/**
 * Our Cache API class
 *
 * @package CacheAPI
 */
class CacheApcu extends CacheApi implements CacheApiInterface {

	/**
	 * {@inheritDoc}
	 */
	public function isSupported($test = false) {

		$supported = function_exists('apcu_fetch') && function_exists('apcu_store');

		if ($test) {
			return $supported;
		}

		return parent::isSupported() && $supported;
	}

	/**
	 * {@inheritDoc}
	 */
	public function connect() {

		return true;
	}
    
    protected function _set($key, $value, $ttl = 0) {

        return $this->putData($key, $value, $ttl);
    }
    
    protected function _get($key) {

        return $this->getData($key);
    }
    
    protected function _exists($key) {

        return (bool) $this->_get($key);
    }
    
    protected function _writeKeys() {


        $this->_set($this->prefix, $this->keys);

        return true;
    }
    
    public function getApcuValues() {
        ini_set('memory_limit', '-1');
        $result = [];
        $values = $this->keys('*');
        if(is_array($values)) {
            foreach($values as $value) {
                $val = $this->getData($value);
                if(!is_null($val) && !is_object($val)) {
                    $result[$value] = !is_array($val) ? Tools::jsonDecode($val, true): $val;
                }
            }
        }
        ksort($result);
        return $result;
    }

	/**
	 * {@inheritDoc}
	 */
	public function getData($key, $ttl = null) {

		$key = $this->prefix . strtr($key, ':/', '-_');

		$value = apcu_fetch($key . 'eph');

		return !empty($value) ? $value : null;
	}

	/**
	 * {@inheritDoc}
	 */
	public function putData($key, $value, $ttl = null) {

		$key = $this->prefix . strtr($key, ':/', '-_');

		// An extended key is needed to counteract a bug in APC.

		if ($value === null) {
			return apcu_delete($key . 'eph');
		} else {
			return apcu_store($key . 'eph', $value, $ttl !== null ? $ttl : $this->ttl);
		}

	}
    
    public function cleanByStartingKey($key) {
        ini_set('memory_limit', '-1');
        $result = $this->_delete($value.'*');
        
        return $result;
    }
    
    protected function _delete($key) {

        return apcu_delete($key . 'eph');
    }
    
    public function flush() {

        return (bool) $this->cleanCache();
    }

	/**
	 * {@inheritDoc}
	 */
	public function cleanCache($type = '') {

		$this->invalidateCache();

		return apcu_clear_cache();
	}

	/**
	 * {@inheritDoc}
	 */
	public function getVersion() {

		return phpversion('apcu');
	}

}

?>