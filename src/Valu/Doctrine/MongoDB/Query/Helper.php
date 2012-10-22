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
     * @return array                      Array of documents or values for requested fields
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
     * @return mixed                Document, field value or array of values
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
     * @return array              Array of documents or values for requested fields
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
     * @return mixed              Document or value(s) of requested field(s)
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
     * @return array                  Documents or value(s) of requested field(s)
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
     * @return array                  Documents or value(s) of requested field(s)
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
                    $this->repository->getClassName()
                );
            }
        } else {
            return $this->documentNames;
        }
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
            $cursor->sort($args['sort']);
        }
    
        if (null !== $args['limit']) {
            $cursor->limit($args['limit']);
        }
    
        if (null !== $args['offset']) {
            $cursor->skip($args['offset']);
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
     * @return string|array|\Doctrine\ODM\MongoDB\Cursor
     */
    private function prepareResult($result, $fields, $mode)
    {
        if (!$result) {
            return $result;
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
}