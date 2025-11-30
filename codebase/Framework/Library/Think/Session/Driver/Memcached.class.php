<?php
// +----------------------------------------------------------------------+
// | ThinkPHP [ Memcached Session drive ]
// +----------------------------------------------------------------------+
//
namespace Think\Session\Driver;
class Memcached{
	
	protected $lifeTime     = 7200;
	protected $sessionName  = '';
	protected $handle       = null;	
	
	
    /**
     * Open Session
	 * @Sinda admin@ipingtai.com
     * @access public 
     * @param string $savePath 
     * @param mixed $sessName  
     */

	public function open($savePath, $sessName) {
		$this->lifeTime     = C('SESSION_EXPIRE') ? C('SESSION_EXPIRE') : $this->lifeTime;
        $options            = array(
            'timeout'       => C('SESSION_TIMEOUT') ? C('SESSION_TIMEOUT') : 1,
            'persistent'    => C('SESSION_PERSISTENT') ? C('SESSION_PERSISTENT') : 0
        );
		$this->handle       = new \Memcached();
		//dump($this->handle);exit;
        $hosts              = explode(',', C('MEMCACHE_HOST'));
        $ports              = explode(',', C('MEMCACHE_PORT'));
        //$usernames           = explode(',', C('MEMCACHE_USERNAME'));
        //$passwords           = explode(',', C('MEMCACHE_PASSWORD'));
		
		//Circular cache information, mainly used for cluster deployment
		foreach ($hosts as $i=>$host) {
            $port           = isset($ports[$i]) ? $ports[$i] : $ports[0];
            //$username       = isset($usernames[$i]) ? $usernames[$i] : $usernames[0];
            //$password       = isset($passwords[$i]) ? $passwords[$i] : $passwords[0];
            $this->handle->addServer($host, $port);
			$this->handle->setOption(\Memcached::OPT_COMPRESSION, false); //Turn off compression
			$this->handle->setOption(\Memcached::OPT_BINARY_PROTOCOL, true); //使用binary二进制协议
			//$this->handle->setSaslAuthData($username,$password); 			
        }
		return true;
	}

    /**
     * Close Session 
     * @access public 
	 
     */
	public function close() {
		$this->handle->close();
		return true;
	}

    /**
     * Read Session 
     * @access public 
     * @param string $sessID 
     */
	public function read($sessID) {
        return $this->handle->get($this->sessionName.$sessID);
	}

    /**
     * Write Session 
     * @access public 
     * @param string $sessID 
     * @param String $sessData  
     */
	public function write($sessID, $sessData) {
		return $this->handle->set($this->sessionName.$sessID, $sessData, 0, $this->lifeTime);
	}

    /**
     * Delete Session 
     * @access public 
     * @param string $sessID 
     */
	public function destroy($sessID) {
		return $this->handle->delete($this->sessionName.$sessID);
	}

    /**
     * Session Garbage collection
     * @access public 
     * @param string $sessMaxLifeTime 
     */
	public function gc($sessMaxLifeTime) {
		return true;
	}
}//End
?>