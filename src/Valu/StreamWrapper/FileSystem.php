<?php
namespace Valu\StreamWrapper;

use \Zend\ServiceManager\ServiceLocatorInterface,
	\Valu\Service\Broker as ServiceBroker;

/**
 * Implementation of PHP's stream wrapper using FileSystem service
 * 
 * This stream wrapper makes FileSystem operations available
 * through special scheme in file functions.
 * 
 * To remove a file from FileSystem service, one might simply
 * type:
 * unlink('valu://path/to/file.pdf');
 * 
 * @author juhasuni
 *
 */
class FileSystem{
    
    public $context;
    
    private $dir = null;
    
    private $nodes = null;
    
    private $file = null;
    
    private $position = 0;
    
    private $fileContent = null;
    
    /**
     * ServiceLocatorInterface
     * 
     * @var ServiceLocatorInterface
     */
    protected static $locator;
    
    public function __construct(){}
    
    public function dir_opendir($path, $options){
    	if($this->fs()->dirExists($path)){
    	    $this->dir = $path;
    	    
    	    return true;
    	}
    	else{
    	    return false;
    	}
    }
    
    public function dir_closedir(){
        $this->dir = null;
        $this->nodes = null;
        
        return true;
    }
    
    public function dir_readdir(){
        
        $files = $this->getNodeList();
        if(!$files) return false;
        
        // no more files in directory
        if(!$files->isValid()){
            return false;
        }
        
        // fetch current
        $file = $files->current();
        
        // advance iterator
        $files->next();
        
        return $file;
    }
    
    public function dir_rewinddir(){
        $files = $this->getNodeList();
        if(!$files) return false;
        
        $files->rewind();
        return true;
    }
    
    public function mkdir($path, $mode, $options){

        $recursive = $options & STREAM_MKDIR_RECURSIVE;
        $this->fs()->createDir($path, array(), $recursive);
        
        return true;
    }
    
    public function rename($path_from, $path_to){
        return $this->fs()->moveNodes($path_from, $path_to);
    }
    
    public function rmdir($path, $options){
        
        if(!$this->fs()->dirExists($path)){
            return false;
        }
        
        $recursive = $options & STREAM_MKDIR_RECURSIVE;
        if(!$recursive && !$this->fs()->isEmptyDir($path)){
            return false;
        }
        
        return $this->fs()->removeDir($path);
    }
    
    public function stream_close(){
        $this->file 		= null;
        $this->fileContent 	= null;
        $this->position		= 0;
    }
    
    public function stream_eof(){
        return $this->position >= strlen($this->fileContent);
    }
    
    /**
     * Not implemented, returns false as should in such case
     */
    public function stream_flush(){
        return false;
    }
    
    public function stream_metadata($path, $option, $var){
        
        /**
         * @todo Support for other options?
         */
        if($option & PHP_STREAM_META_TOUCH){
            try{
                $specs = array('modified' => new \DateTime());
                
                if(!$this->fs()->fileExists($path) && !$this->fs()->dirExists($path)){
                    $this->fs()->createFile(null, $path, $specs);
                }
                
	            $this->fs()->setNodeSpecs(
	            	$path, 
	            	$specs
	            );
            }
            catch(\Exception $e){
                return false;
            }
            
            return true;
        }
        
        return false;
    }
    
    public function stream_open($path, $mode, $options, &$opened_path){
        
        $this->file = $path;
        
        $usePath 		= (bool) ($options & STREAM_USE_PATH);
        $reportErrors 	= (bool) ($options & STREAM_REPORT_ERRORS);
        
        if($usePath){
            $opened_path = $path;
        }
        
        $fileExists	= $this->fs()->fileExists($path);
        
        /**
         * Handle mode correctly:
         * - set correct "caret" position
         * - create file when needed
         * - truncate file when needed
         * 
         * @link http://fi.php.net/manual/en/function.chmod.php
         */
        switch ($mode){
        	case 'r':
        	case 'r+':
        	    $this->position = 0;
        	    
        	    if(!$fileExists){
        	        return false;
        	    }
        	    
        	    break;
        	    
        	case 'w':
        	case 'w+':
        	case 'a':
        	case 'a+':
        	case 'x':
        	case 'x+':
        	case 'c':
        	case 'c+':
        	    $this->position = 0;
        	    
        	    if(!$fileExists){
        	        $this->fs()->createFile('', $path);
        	        $fileExists = true;
        	    }
        	    else if($mode == 'w' || $mode == 'w+'){
        	        $this->fs()->setFileBytes($path, '');
        	    }
        	    else if($mode == 'x' || $mode == 'x+'){
        	        return false;
        	    }
        	    
        	    if($mode == 'a' || $mode == 'a+'){
        	        $this->readFile();
        	        $this->position = strlen($this->fileContent);
        	    }
        	    
        		break;
        }
        
        return true;
    }
    
    public function stream_read($count){
        
        $this->readFile();
        
        $data = substr($this->fileContent, $this->position, $count);
        $this->position += strlen($data);
        
        return $data;
    }
    
    public function stream_seek($offset, $whence){
        
        $this->readFile();
        
        switch ($whence) {
            case SEEK_SET:
                if ($offset < strlen($this->fileContent) && $offset >= 0) {
                    $this->position = $offset;
                    return true;
                } else {
                    return false;
                }
                break;
            case SEEK_CUR:
                if ($offset >= 0) {
                    $this->position += $offset;
                    return true;
                } else {
                    return false;
                }
                break;
            case SEEK_END:
                if (strlen($this->fileContent) + $offset >= 0) {
                    $this->position = strlen($this->fileContent) + $offset;
                    return true;
                } else {
                    return false;
                }
                break;
            default:
                return false;
        }
    }
    
    public function stream_stat(){
        return $this->url_stat($this->file, STREAM_URL_STAT_QUIET);
    }
    
    public function stream_tell(){
        return $this->position;
    }
    
    public function stream_write($data){
        
        $this->readFile();
        
        $left 	= substr($this->fileContent, 0, $this->position);
        $right 	= substr($this->fileContent, $this->position + strlen($data));
        
        if($this->fs()->writeFile($this->file, $left . $data . $right)){
            $this->position += strlen($data);
            return strlen($data);
        }
        else{
            return 0;
        }
    }
    
    public function unlink($path){
        return $this->fs()->removeFile($path);
    }
    
    public function url_stat($path, $flags){
        
        $quiet	= $flags & STREAM_URL_STAT_QUIET;
        $link	= $flags & STREAM_URL_STAT_LINK;
        
        $stat = array(
        	'dev' 	=> 0,
        	'ino' 	=> 0,
        	'mode' 	=> 0777, //@todo Should mode be determined based on current user?
        	'nlink' => 0,
        	'uid' 	=> 0,
        	'gid' 	=> 0,
        	'rdev' 	=> 0,
        	'size' 	=> 0,
        	'atime' => 0,
        	'mtime' => 0,
        	'ctime' => 0,
        	'blksize' 	=> -1,
        	'blocks' 	=> -1
        );
        
        try{
			$specs = $this->fs()->getFileSpecs(
	        	$this->file,
	        	array('id', 'filesize', 'modified', 'changed')
	        );
			
			$stat['dev'] = $specs['id'];
			$stat['size'] = $specs['filesize'];
			$stat['atime'] = $specs['modified'];
			$stat['mtime'] = $specs['modified'];
			$stat['ctime'] = $specs['changed'];
        }
        catch(\Exception $e){
            if(!$quiet){
                trigger_error($e->getMessage(), E_WARNING);
            }
        }
        
        return $stat;
    }
    
    protected function readFile(){
        
        if(!$this->file){
            return false;
        }
        
        if($this->fileContent === null){
            $bytes = $this->fs()->read($this->file);
            if($bytes === null) $bytes = '';
            
            $this->fileContent = $bytes;
        }
    }
    
    /**
     * Reads current open directory and returns ArrayIterator
     * 
     * @return boolean|\ArrayIterator
     */
    protected function getNodeList(){
        if(!$this->dir){
        	return false;
        }
        
        if($this->nodes === null){
        	$this->nodes = new \ArrayIterator(
        			$this->fs()->getNodeList($this->dir)
        	);
        }
        
        return $this->nodes;
    }
    
    /**
     * Get access to FileSystem service
     * 
     * @return \ValuFileSystem\Service\FileSystem
     */
    protected function fs(){
        $broker = $this->getServiceBroker();
        return $broker->service('FileSystem');
    }
    
    /**
     * Retrieve service broker instance
     * 
     * @return ServiceBroker
     */
    protected function getServiceBroker(){
        return self::$locator->get('ServiceBroker');
    }
    
    public static function setServiceLocator(ServiceLocatorInterface $locator){
        self::$locator = $locator;
    }
}