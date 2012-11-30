<?php
namespace Valu\Doctrine\MongoDB\Query;

use Valu\Selector\Parser\SelectorParser;
use Doctrine\ODM\MongoDB\Query\Expr;
use Doctrine\ODM\MongoDB\DocumentRepository;
use Doctrine\ODM\MongoDB\Query\Builder;

/**
 * DoctineMongoDB query helper
 * 
 * @author juhasuni
 *
 */
class Helper
{
    /**
     * Mongo ID type
     * 
     * @var string
     */
    const ID_MONGO = 'mongoid';
    
    /**
     * UUID3 type
     * 
     * @var string
     */
    const ID_UUID3 = 'uuid3';
    
    /**
     * UUID5 type
     * 
     * @var string
     */
    const ID_UUID5 = 'uuid5';
    
    /**
     * Indicates find operation for a single entity
     *
     * @var string
     */
    const FIND_ONE = 1;
    
    /**
     * Indicates find operation for multiple entities
     * 
     * @var string
     */
    const FIND_MANY = 2;
    
    /**
     * Universal selector
     * 
     * @var string
     */
    const UNIVERSAL_SELECTOR = '*';
    
    /**
     * ID selector prefix
     * 
     * @var string
     */
    const ID_PREFIX = '#';
    
    /**
     * Command identifier prefix
     * 
     * @var string
     */
    const CMD = '@';
    
    /**
     * Document repository
     * 
     * @var \Doctrine\ODM\MongoDB\DocumentRepository
     */
    protected $repository;
    
    /**
     * Associated document names
     * 
     * @var array
     */
    protected $documentNames = null;
    
    /**
     * Length of the ID for autodetection
     * 
     * @var int
     */
    protected $idLength = null;
    
    /**
     * ID type
     * 
     * @var string
     */
    protected $idType = null;
    
    /**
     * Selector options (as passed to selector constructor)
     * 
     * @var array
     */
    protected $selectorOptions = array();
    
    public function __construct(DocumentRepository $repository)
    {
        $this->setDocumentRepository($repository);
    }
    
    /**
     * Perform query and retrieve matched documents or fields
     * 
     * Query may be a string, in which case it is treated as
     * a selector. Query may also be an associative array, 
     * in which case it is passed directly as query criteria to
     * query builder. Last, query may be an array with numeric
     * indexes, in which case it is considered as an array of
     * sub queries (match any).
     * 
     * @param mixed $query                Query
     * @param null|string|array $fields   Field(s) to return
     * @return array                      Array of documents or values for requested fields.
     *                                    Returns empty array if query doesn't match.
     */
    public function query($query, $fields = null)
    {
        return $this->doQuery($query, $fields);
    }
    
    /**
     * Query and retrieve exactly one document or specified
     * document fields
     * 
     * @see Helper::query()        for description of parameter usage
     * 
     * @param string|array $query   Query
     * @param string|array $fields  Field(s) to return
     * @return mixed                Document, field value or array of values. 
     *                              Returns null if query doesn't match.
     */
    public function queryOne($query, $fields = null)
    {
        return $this->doQuery($query, $fields, self::FIND_ONE);
    }
    
    /**
     * Find documents by CSS style selector
     * 
     * @see Helper::query()       for instructions how to use $fields parameter
     * 
     * @param string $selector    CSS style selector
     * @param string $fields      Field(s) to return    
     * @return array              Array of documents or values for requested fields.
     *                            Returns empty array if selector doesn't match.
     */
    public function findBySelector($selector, $fields = null)
    {
        return $this->doFindBySelector($selector, $fields);
    }
    
    /**
     * Find a document by CSS style selector
     * 
     * @see Helper::findBySelector()    for usage details
     * 
     * @param string $selector    CSS style selector
     * @param string $fields      Field(s) to return    
     * @return mixed              Document or value(s) of requested field(s).
     *                            Returns null if selector doesn't match.
     */
    public function findOneBySelector($selector, $fields = null)
    {
        return $this->doFindBySelector($selector, $fields, self::FIND_ONE);
    }
    
    /**
     * Find documents by criteria
     * 
     * The criteria array is passed directly to query builder.
     * 
     * @param array $query            Query criteria
     * @param string|array $fields    Field(s) to return
     * @return array                  Documents or value(s) of requested field(s).
     *                                Returns empty array if query doesn't match.
     */
    public function findByArray(array $query, $fields = null)
    {
        return $this->doFindByArray($query, $fields);
    }
    
    /**
     * Find one document by criteria
     *
     * The criteria array is passed directly to query builder.
     *
     * @param array $query            Query criteria
     * @param string|array $fields    Field(s) to return
     * @return array                  Documents or value(s) of requested field(s).
     *                                Returns null if query doesn't match.
     */
    public function findOneByArray(array $query, $fields = null)
    {
        return $this->doFindByArray($query, $fields, self::FIND_ONE);
    }
    
    /**
     * Apply query to existing expression
     *
     * @param string|array $query
     * @param Expr $expression
     * @throws \Exception
     * @throws \InvalidArgumentException
     */
    public function applyQuery(Builder $queryBuilder, $query, Expr $expression)
    {
        if(is_string($query)) {
            if (substr($query, 0, 1) !== self::ID_PREFIX 
                && ($id = $this->detectId($query)) !== null) {
                
                $query = self::ID_PREFIX . $id;
            }
            
            $this->applySelector($queryBuilder, $query, $expression);
            
        } elseif (is_array($query)) {
            throw new \Exception('Not implemented');
        } else {
            throw new \InvalidArgumentException('Invalid query provided; string or array expected');
        }
    }
    
    /**
     * Applies selector either to an existing expression or 
     * by initializing a new expression
     * 
     * @param Builder $queryBuilder
     * @param string $selector
     * @param Expr $expression
     */
    public function applySelector(Builder $queryBuilder, $selector, Expr $expression = null)
    {
        if($selector instanceof SelectorParser){
            $definition = $selector;
        } else {
            $definition = SelectorParser::parseSelector($selector);
        }

        $selector = new Selector(
            $definition, 
            $this->repository->getDocumentManager(), 
            $this->getDocumentNames(),
            $this->getSelectorOptions()
        );
        
        $selector->extendQuery($queryBuilder, $expression);
    }
    
    /**
     * Test whether given query parameter represents
     * an empty query
     * 
     * @param mixed $query
     * @return boolean
     */
    public function isEmptyQueryParam($query)
    {
        if (is_string($query)) {
            return trim($query) == '';
        } elseif (is_array($query)) {
            return sizeof($query) == 0;
        } else {
            return false;
        }
    }
    
    /**
     * Retrieve selector options
     * 
     * @return array
     */
    public function getSelectorOptions()
    {
        return $this->selectorOptions;
    }
    
    /**
     * Set selector options
     * 
     * @param array $options
     * @return Helper
     */
    public function setSelectorOptions(array $options)
    {
        $this->selectorOptions = $options;
        return $this;
    }
    
    /**
     * Retrieve associated document names
     * 
     * @return array
     */
    public function getDocumentNames()
    {
        if ($this->documentNames === null) {
            $documents = $this->repository->getClassMetadata()->discriminatorMap;
            
            if (sizeof($documents)) {
                return $documents;
            } else {
                return array(
                    'default' => $this->repository->getClassName()
                );
            }
        } else {
            return $this->documentNames;
        }
    }
    
    /**
     * Enable ID autodetection
     * 
     * @param string $type ID type
     * @return \Valu\Doctrine\MongoDB\Query\Helper
     */
    public function enableIdDetection($type = self::ID_MONGO)
    {
        $this->idType = $type;
        
        switch ($this->idType) {
            case self::ID_MONGO:
                $this->idLength = 24;
                break;
            case self::ID_UUID3:
            case self::ID_UUID5:
                $this->idLength = 32;
                break;
            default:
                throw new \InvalidArgumentException('Unrecognized ID type');
                break;
        }
        
        return $this;
    }
    
    /**
     * Disable ID autodetection
     * 
     * @return \Valu\Doctrine\MongoDB\Query\Helper
     */
    public function disableIdDetection()
    {
        $this->idLength = null;
        return $this;
    }
    
    /**
     * Set associated document names
     * 
     * @param array $names
     * @return \Valu\Doctrine\MongoDB\Query\Helper
     */
    public function setDocumentNames(array $names)
    {
        $this->documentNames = $names;
        return $this;
    }
    
    /**
     * Retrieve document repository
     * 
     * @return \Doctrine\ODM\MongoDB\DocumentRepository
     */
    public function getDocumentRepository()
    {
        return $this->repository;
    }
    
    /**
     * Set document repository associated with this helper
     * 
     * @param DocumentRepository $repository
     */
    public function setDocumentRepository(DocumentRepository $repository)
    {
        $this->repository = $repository;
        return $this;
    }
    
    /**
     * Retrieve document manager
     * 
     * @return \Doctrine\ODM\MongoDB\DocumentManager
     */
    public function getDocumentManager()
    {
        return $this->getDocumentRepository()->getDocumentManager();
    }
    
    /**
     * Performs query
     * 
     * @param mixed $query
     * @param array $fields
     * @param int $mode
     * @return multitype:|\Valu\Doctrine\MongoDB\Query\mixed|Ambigous <string, multitype:, \Doctrine\ODM\MongoDB\Cursor, NULL, \Valu\Doctrine\MongoDB\Query\array|\Doctrine\ODM\MongoDB\Cursor, multitype:unknown >|NULL
     */
    private function doQuery($query, $fields = null, $mode = self::FIND_MANY)
    {
        if(is_string($query)) {
            if ($mode == self::FIND_MANY) {
                return $this->findBySelector($query, $fields);
            } else {
                return $this->findOneBySelector($query, $fields);
            }
        } elseif(is_array($query)) {
            
            if(empty($query) || $this->isAssociativeArray($query)) {
                if ($mode == self::FIND_MANY) {
                    return $this->findByArray($query, $fields);
                } else {
                    return $this->findOneByArray($query, $fields);
                }
            } else {
                
                $qb = $this->repository->createQueryBuilder();
                $this->applyFields($qb, $fields);
                 
                foreach ($query as $subQuery) {
                    $expr = $qb->expr();
                    $this->applyQuery($qb, $subQuery, $expr);
        
                    $qb->addOr($expr);
                }
        
                if ($mode == self::FIND_MANY) {
                    $result = $qb->getQuery()
                    ->execute();
                } else {
                    $qb->limit(1);
                    $result = $qb->getQuery()
                        ->getSingleResult();
                }
                
                return $this->prepareResult($result, $fields, $mode);
            }
             
        } else {
            if ($mode == self::FIND_MANY) {
                return array();
            } else {
                return null;
            }
        }
    }
    
    /**
     * Perform find by array of criteria
     * 
     * @param array $query
     * @param array|string $fields
     * @param string $mode
     * @return null|array|\Doctrine\ODM\MongoDB\Cursor
     */
    private function doFindByArray(array $query, $fields = null, $mode = self::FIND_MANY)
    {
        $args = array();
    
        // Parse internal commands
        foreach (array('sort', 'limit', 'offset') as $cmd) {
            if (array_key_exists(self::CMD . $cmd, $query)) {
                $args[$cmd] = $query[self::CMD . $cmd];
            } else {
                $args[$cmd] = null;
            }
        }
    
        $qb = $this->repository->createQueryBuilder();
        $this->applyFields($qb, $fields);
        
        foreach ($this->getDocumentNames() as $document) {
            $documentQuery = $this->getUow()
                ->getDocumentPersister($document)
                ->prepareQuery($query);
            
            $expr = $qb->expr();
            $expr->setQuery($documentQuery);
            
            $qb->addOr($expr);
        }
    
        // Apply internal commands
        if (null !== $args['sort']) {
            $qb->sort($args['sort']);
        }
    
        if (null !== $args['limit']) {
            $qb->limit($args['limit']);
        }
    
        if (null !== $args['offset']) {
            $qb->skip($args['offset']);
        }
    
        if ($mode == self::FIND_MANY) {
            $result = $qb->getQuery()->execute();
        } else {
            $qb->limit(1);
            $result = $qb->getQuery()->getSingleResult();
        }
        
        return $this->prepareResult($result, $fields, $mode);
    }
    
    /**
     * Perform find by selector string
     * 
     * @param string $selector
     * @param string|array $fields
     * @param string $mode
     */
    private function doFindBySelector($selector, $fields = null, $mode = self::FIND_MANY)
    {
        if ($selector == '') {
            return $mode == self::FIND_MANY ? array() : null;
        }
        
        // Detect ID
        $id = $this->detectId($selector);
        
        // Find documents using faster methods 
        // when ID selector is used
        if ($id !== null && $fields === null && $mode == self::FIND_ONE) {
            return $this->getDocumentRepository()->findOneBy(array('id' => $id));
        } elseif ($id !== null) {
            return $this->doFindByArray(array('id' => $id), $fields, $mode);
        }
        
        $qb = $this->repository->createQueryBuilder();
        $this->applyFields($qb, $fields);
        
        if($selector && ($selector !== self::UNIVERSAL_SELECTOR)){
            $this->applySelector($qb, $selector);
        }
        
        if ($mode == self::FIND_ONE) {
            $qb->limit(1);
            $result = $qb->getQuery()
                ->getSingleResult();
        } else {
            $result = $qb->getQuery()
                ->execute();
        }
        
        return $this->prepareResult($result, $fields, $mode);
    }
    
    /**
     * Apply select() for each field
     * 
     * @param Builder $queryBuilder
     * @param array|string $fields
     */
    private function applyFields(Builder $queryBuilder, $fields)
    {
        if ($fields) {
        
            $queryBuilder->hydrate(false);
        
            if (is_string($fields)) {
                $queryBuilder->select($fields);
            } else {
                call_user_func_array(array($queryBuilder, 'select'), $fields);
            }
        }
    }
    
    /**
     * Prepares query result
     * 
     * @param array|\Doctrine\ODM\MongoDB\Cursor $result
     * @param null|string|array $fields
     * @param int $mode
     * @return string|array|\Doctrine\ODM\MongoDB\Cursor|null
     */
    private function prepareResult($result, $fields, $mode)
    {
        if ($result === null) {
            return null;
        }
        
        if (is_string($fields)) {
            
            $fields = explode('.', $fields);
            $fields = array_pop($fields);
            
            if ($fields == 'id') {
                $fields = '_id';
            }
            
            if ($mode == self::FIND_ONE) {
                return array_key_exists($fields, $result)
                    ? $result[$fields] : null;
            } else {
                $filtered = array();
                
                foreach ($result as $data) {
                    $filtered[] = $data[$fields];    
                }
                
                return $filtered;
            }
        }
        
        return $result;
    }
    
    /**
     * Retrieve current unit of work
     * 
     * @return \Doctrine\ODM\MongoDB\UnitOfWork
     */
    private function getUow()
    {
        return $this->getDocumentManager()->getUnitOfWork();
    }
    
    /**
     * Test whether given array is associative
     * 
     * @param array $array
     * @return boolean    True if associative (and not empty)
     */
    private function isAssociativeArray(array $array)
    {
        return (array_keys($array) !== range(0, count($array) - 1));
    }
    
    /**
     * Detect if value represents ID
     * 
     * @param string $value
     * @return string|NULL
     */
    private function detectId($value)
    {
        $matchLength = false;
        
        if ($this->idLength !== null
            && strlen($value) == $this->idLength) {
            
            $matchLength = true;
        } elseif ($this->idLength !== null
                  && strlen($value) == $this->idLength+1
                  && substr($value, 0, 1) == self::ID_PREFIX) {
            
            $matchLength = true;
            $value = substr($value, 1);
        }
        
        if ($matchLength && ctype_alnum($value)) {
            return $value;
        } else {
            return null;
        }
    }
}