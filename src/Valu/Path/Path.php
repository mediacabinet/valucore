<?php
namespace Valu\Path;

class Path implements \Iterator, \ArrayAccess{
	
	const PATH_SEPARATOR = '/';
	
	/**
	 * Path items as an array
	 * 
	 * @var array
	 */
	protected $items = array();
	
	/**
	 * Iterator position
	 * 
	 * @var int
	 */
	private $position = 0;

	/**
	 * Construct a new path from string
	 * 
	 * @param string|array $path
	 * @throws \InvalidArgumentException
	 */
	public function __construct($path)
	{
		if(is_array($path)){
			$this->fromArray($path);
		}
		else{
			$this->setPath($path);
		}
	}
	
	/**
	 * Set path
	 * 
	 * @param string $path
	 * @throws \InvalidArgumentException
	 * @return Path
	 */
	public function setPath($path)
	{
		$path = self::cleanup($path);
		
		if(!self::isValid($path)){
			throw new \InvalidArgumentException('Path '.$path.' is not valid');
		}

		$items = self::split($path);
        $rm    = 0;
        
        // Resolve ../ and ./
		for ($i = sizeof($items)-1; $i > 0; $i--) {
		    $item = $items[$i];
		    
		    if ($item == '.') {
		        unset($items[$i]);
		    } elseif ($item == '..') {
		        unset($items[$i]);
		        $rm++;
		    } elseif ($rm) {
		        unset($items[$i]);
		        $rm--;
		    }
		}
		
		$this->items = array_values($items);
		
		return $this;
	}
	
	/**
	 * Retrieve current path
	 * 
	 * @return string
	 */
	public function getPath()
	{
		return self::join($this->items);
	}
	
	/**
	 * Retrieve array of sub paths
	 * 
	 * E.g. sub paths of path share/videos/hd are
	 * share, share/videos and share/videos/hd 
	 * 
	 * @return array
	 */
	public function getSubPaths(){
		
		$arr	= array();
		$paths	= array(
			new Path('/')
		);
		
		if(sizeof($this->items)){
			foreach ($this->items as $item) {
				$arr[] 		= $item;
				$paths[]	= new Path($arr);
			}
		}
		
		return $paths;
	}
	
	/**
	 * Test if path is root path
	 * 
	 * @return boolean
	 */
	public function isRoot()
	{
		return sizeof($this->items) == 0;
	}
	
	/**
	 * Retrieve basename of path (last path item)
	 * 
	 * @return string
	 */
	public function getBasename()
	{
		$count = $this->count();
		return $count ? $this->items[$count-1] : '';
	}
	
	/**
	 * Retrieve parent path
	 * 
	 * @return Path
	 */
	public function getParent()
	{
	    return new Path(array_slice($this->items, 0, -1));
	}
	
	/**
	 * Appends items to path
	 * 
	 * @param string|array $path
	 * @throws \Exception
	 * @throws \InvalidArgumentException
	 * @return \ValuFileSystem\Path
	 */
	public function append($path)
	{
		if(is_string($path)){
			if(substr($path, 0, 1) == self::PATH_SEPARATOR){
				throw new \Exception('Joined path must not begin with a slash');
			}
			else{
				$path = self::split($path);
			}
		}
		
		if(!is_array($path)){
			throw new \InvalidArgumentException('Illegal value for $path; array or string expected');
		}
		
		$this->items = array_merge($this->items, $path);
		
		return $this;
	}

	/**
	 * Retrieve path items as an array
	 * 
	 * @return array
	 */
	public function toArray(){
		return $this->items;
	}
	
	/**
	 * Populate path from array
	 * 
	 * @param array $items
	 * @return Path
	 */
	public function fromArray(array $items)
	{
		return $this->setPath(self::join($items));
	}
	
	/**
	 * Retrieve number of items in path
	 * 
	 * @return int
	 */
	public function count(){
		return sizeof($this->items);
	}
	
	/**
	 * Retrieve path in query compatible format
	 * 
	 * @return string
	 */
	public function getQueryString()
	{
	    return str_replace(' ', '\\ ', $this->__toString());
	}
	
	/**
	 * Convert to string
	 * 
	 * @return string
	 */
	public function __toString()
	{
		return $this->getPath();
	}

	/* (non-PHPdoc)
	 * @see Iterator::current()
	 */
	public function current() {
		return $this->items[$this->position];
	}

	/* (non-PHPdoc)
	 * @see Iterator::key()
	 */
	public function key() {
		return $this->position;
	}

	/* (non-PHPdoc)
	 * @see Iterator::next()
	 */
	public function next() {
		++$this->position;
	}

	/* (non-PHPdoc)
	 * @see Iterator::rewind()
	 */
	public function rewind() {
		$this->position = 0;
	}

	/* (non-PHPdoc)
	 * @see Iterator::valid()
	 */
	public function valid() {
		return isset($this->items[$this->position]);
	}

	/* (non-PHPdoc)
	 * @see ArrayAccess::offsetExists()
	 */
	public function offsetExists($offset) {
		 return isset($this->items[$offset]);
	}

	/* (non-PHPdoc)
	 * @see ArrayAccess::offsetGet()
	 */
	public function offsetGet($offset) {
		return isset($this->items[$offset]) ? $this->items[$offset] : null;
	}

	/* (non-PHPdoc)
	 * @see ArrayAccess::offsetSet()
	 */
	public function offsetSet($offset, $value) {
		if (is_null($offset)) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
	}

	/* (non-PHPdoc)
	 * @see ArrayAccess::offsetUnset()
	 */
	public function offsetUnset($offset) {
		unset($this->items[$offset]);
	}
	
	/**
	 * Test if path is valid
	 * 
	 * @param string $path
	 * @return boolean
	 */
	public static function isValid($path)
	{
		$char = strlen($path) ? substr($path, 0, 1) : '';
		
		if(!in_array($char, array('/', '#', '$', '\\'))){
			return false;
		}
		else if(in_array($char, array('#', '$'))){
		    $path = substr($path, 1);
		}

		return true;
	}
	
	/**
	 * Cleanup path
	 * 
	 * Trims path and replaces multiple preceeding/traling
	 * slashes with a single slash. If slash is missing from
	 * the beginning of the path, it is automatically prepended.
	 * Cleanup also removes useless character escapes.
	 * 
	 * @param string $path
	 * @return mixed
	 */
	public static function cleanup($path)
	{
	    $path = rtrim($path);
        $path = ltrim($path, ' /');
        $path = '/' . $path;
        $path = preg_replace('#/+$#', '/', $path);
        $path = stripslashes($path);
	    
		return $path;
	}
	
	/**
	 * Join items in array as a path
	 * 
	 * @param array $items
	 * @return string
	 */
	public static function join(array $items)
	{
		return self::PATH_SEPARATOR . 
				ltrim(
					implode(self::PATH_SEPARATOR, $items), 
					self::PATH_SEPARATOR
		);
	}
	
	/**
	 * Split path as array
	 *  
	 * @param string $path
	 * @return array
	 */
	public static function split($path)
	{
		
		$path	= ltrim($path, self::PATH_SEPARATOR);
		$items	= explode(self::PATH_SEPARATOR, $path);
		
		return (sizeof($items) == 1 && $items[0] == '') ? array() : $items;
	}
}