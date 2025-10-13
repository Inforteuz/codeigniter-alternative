<?php
/**
 * BaseModel.php
 *
 * Enhanced base model class with CodeIgniter 4 inspired features.
 * Provides comprehensive database operations and query builder functionality.
 *
 * @package    CodeIgniter Alternative
 * @subpackage System
 * @version    2.0.0
 * @date       2024-12-12
 *
 * @description
 * Enhanced functionality includes:
 *
 * 1. **Advanced Query Builder**
 * 2. **Batch Operations**
 * 3. **Advanced Model Features**
 * 4. **Search & Filter**
 * 5. **NEW: Relationship Management** (hasOne, hasMany, belongsTo, belongsToMany)
 * 6. **NEW: Advanced Pagination**
 * 7. **NEW: Model Scopes**
 * 8. **NEW: Data Casting & Mutators**
 * 9. **NEW: Chunk Processing**
 * 10. **NEW: Upsert & Replace Operations**
 */
namespace System;
use System\Database\Database;
use System\Core\DebugToolbar;

class BaseModel
{
    protected $db;
    protected $table;
    protected $primaryKey = 'id';
    protected $lastWhere = '';
    protected $lastParams = [];
    protected $lastOrder = '';
    protected $lastLimit = '';
    protected $lastSelect = '*';
    protected $lastJoin = '';
    protected $lastGroupBy = '';
    protected $lastHaving = '';
    
    // Enhanced features
    protected $useSoftDeletes = false;
    protected $deletedField = 'deleted_at';
    protected $useTimestamps = false;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $validationRules = [];
    protected $callbacks = [
        'beforeInsert' => [],
        'afterInsert' => [],
        'beforeUpdate' => [],
        'afterUpdate' => [],
        'beforeDelete' => [],
        'afterDelete' => [],
        'beforeFind' => [],
        'afterFind' => []
    ];
    
    // Data casting
    protected $casts = [];
    
    // Hidden fields (not returned in results)
    protected $hidden = [];
    
    // Appended fields (computed properties)
    protected $appends = [];
    
    // Fillable fields (mass assignment protection)
    protected $fillable = [];
    protected $guarded = ['*'];
    
    // Relationships cache
    protected $relationships = [];
    
    // Query scopes
    protected $scopes = [];
    
    // Query result cache
    protected $resultArray = [];
    protected $resultObject = [];
    protected $numRows = null;
    protected $resultID = null;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ===== QUERY BUILDER - SELECT METHODS =====

    /**
     * Set SELECT fields
     * @param string|array $fields
     * @return self
     */
    public function select($fields = '*')
    {
        if (is_array($fields)) {
            $this->lastSelect = implode(', ', $fields);
        } else {
            $this->lastSelect = $fields;
        }
        return $this;
    }

    /**
     * Add DISTINCT to SELECT
     * @param string|array $fields
     * @return self
     */
    public function distinct($fields = '*')
    {
        if (is_array($fields)) {
            $this->lastSelect = 'DISTINCT ' . implode(', ', $fields);
        } else {
            $this->lastSelect = 'DISTINCT ' . $fields;
        }
        return $this;
    }

    /**
     * Add SELECT MAX
     * @param string $field
     * @param string $alias
     * @return self
     */
    public function selectMax($field, $alias = '')
    {
        $alias = $alias ?: $field . '_max';
        $this->lastSelect = "MAX({$field}) AS {$alias}";
        return $this;
    }

    /**
     * Add SELECT MIN
     * @param string $field
     * @param string $alias
     * @return self
     */
    public function selectMin($field, $alias = '')
    {
        $alias = $alias ?: $field . '_min';
        $this->lastSelect = "MIN({$field}) AS {$alias}";
        return $this;
    }

    /**
     * Add SELECT AVG
     * @param string $field
     * @param string $alias
     * @return self
     */
    public function selectAvg($field, $alias = '')
    {
        $alias = $alias ?: $field . '_avg';
        $this->lastSelect = "AVG({$field}) AS {$alias}";
        return $this;
    }

    /**
     * Add SELECT SUM
     * @param string $field
     * @param string $alias
     * @return self
     */
    public function selectSum($field, $alias = '')
    {
        $alias = $alias ?: $field . '_sum';
        $this->lastSelect = "SUM({$field}) AS {$alias}";
        return $this;
    }

    /**
     * Add SELECT COUNT
     * @param string $field
     * @param string $alias
     * @return self
     */
    public function selectCount($field = '*', $alias = 'count')
    {
        $this->lastSelect = "COUNT({$field}) AS {$alias}";
        return $this;
    }

    // ===== QUERY BUILDER - JOIN METHODS =====

    /**
     * Add JOIN clause
     * @param string $table
     * @param string $condition
     * @param string $type
     * @return self
     */
    public function join($table, $condition, $type = 'INNER')
    {
        $this->lastJoin .= " {$type} JOIN {$table} ON {$condition}";
        return $this;
    }

    /**
     * Add LEFT JOIN clause
     * @param string $table
     * @param string $condition
     * @return self
     */
    public function leftJoin($table, $condition)
    {
        return $this->join($table, $condition, 'LEFT');
    }

    /**
     * Add RIGHT JOIN clause
     * @param string $table
     * @param string $condition
     * @return self
     */
    public function rightJoin($table, $condition)
    {
        return $this->join($table, $condition, 'RIGHT');
    }

    // ===== QUERY BUILDER - WHERE METHODS =====

    /**
     * Enhanced WHERE with OR support
     * @param mixed $conditions
     * @param string $operator
     * @return self
     */
    public function where($conditions = [], $operator = 'AND')
    {
        $where = $this->buildWhereClause($conditions, $operator);
        
        if (!empty($this->lastWhere)) {
            $this->lastWhere .= " {$operator} " . ltrim($where['sql'], 'WHERE ');
        } else {
            $this->lastWhere = $where['sql'];
        }
        
        $this->lastParams = array_merge($this->lastParams, $where['params']);
        return $this;
    }

    /**
     * Add OR WHERE condition
     * @param mixed $conditions
     * @return self
     */
    public function orWhere($conditions = [])
    {
        return $this->where($conditions, 'OR');
    }

    /**
     * Add WHERE IN condition
     * @param string $field
     * @param array $values
     * @return self
     */
    public function whereIn($field, $values)
    {
        $placeholders = [];
        $params = [];
        
        foreach ($values as $i => $value) {
            $paramName = $field . '_in_' . $i;
            $placeholders[] = ':' . $paramName;
            $params[$paramName] = $value;
        }
        
        $condition = "{$field} IN (" . implode(', ', $placeholders) . ")";
        
        if (!empty($this->lastWhere)) {
            $this->lastWhere .= " AND " . $condition;
        } else {
            $this->lastWhere = "WHERE " . $condition;
        }
        
        $this->lastParams = array_merge($this->lastParams, $params);
        return $this;
    }

    /**
     * Add WHERE NOT IN condition
     * @param string $field
     * @param array $values
     * @return self
     */
    public function whereNotIn($field, $values)
    {
        $placeholders = [];
        $params = [];
        
        foreach ($values as $i => $value) {
            $paramName = $field . '_not_in_' . $i;
            $placeholders[] = ':' . $paramName;
            $params[$paramName] = $value;
        }
        
        $condition = "{$field} NOT IN (" . implode(', ', $placeholders) . ")";
        
        if (!empty($this->lastWhere)) {
            $this->lastWhere .= " AND " . $condition;
        } else {
            $this->lastWhere = "WHERE " . $condition;
        }
        
        $this->lastParams = array_merge($this->lastParams, $params);
        return $this;
    }

    /**
     * Add WHERE NULL condition
     * @param string $field
     * @return self
     */
    public function whereNull($field)
    {
        $condition = "{$field} IS NULL";
        
        if (!empty($this->lastWhere)) {
            $this->lastWhere .= " AND " . $condition;
        } else {
            $this->lastWhere = "WHERE " . $condition;
        }
        
        return $this;
    }

    /**
     * Add WHERE NOT NULL condition
     * @param string $field
     * @return self
     */
    public function whereNotNull($field)
    {
        $condition = "{$field} IS NOT NULL";
        
        if (!empty($this->lastWhere)) {
            $this->lastWhere .= " AND " . $condition;
        } else {
            $this->lastWhere = "WHERE " . $condition;
        }
        
        return $this;
    }

    /**
     * WHERE LIKE condition
     * @param string $field
     * @param string $value
     * @param string $position (before, after, both)
     * @return self
     */
    public function like($field, $value, $position = 'both')
    {
        $paramName = $field . '_like_' . count($this->lastParams);
        
        switch ($position) {
            case 'before':
                $value = "%{$value}";
                break;
            case 'after':
                $value = "{$value}%";
                break;
            case 'both':
            default:
                $value = "%{$value}%";
                break;
        }
        
        $condition = "{$field} LIKE :{$paramName}";
        
        if (!empty($this->lastWhere)) {
            $this->lastWhere .= " AND " . $condition;
        } else {
            $this->lastWhere = "WHERE " . $condition;
        }
        
        $this->lastParams[$paramName] = $value;
        return $this;
    }

    /**
     * Add WHERE NOT LIKE condition
     * @param string $field
     * @param string $value
     * @param string $position
     * @return self
     */
    public function notLike($field, $value, $position = 'both')
    {
        $paramName = $field . '_not_like_' . count($this->lastParams);
        
        switch ($position) {
            case 'before':
                $value = "%{$value}";
                break;
            case 'after':
                $value = "{$value}%";
                break;
            case 'both':
            default:
                $value = "%{$value}%";
                break;
        }
        
        $condition = "{$field} NOT LIKE :{$paramName}";
        
        if (!empty($this->lastWhere)) {
            $this->lastWhere .= " AND " . $condition;
        } else {
            $this->lastWhere = "WHERE " . $condition;
        }
        
        $this->lastParams[$paramName] = $value;
        return $this;
    }

    /**
     * Add WHERE BETWEEN condition
     * @param string $field
     * @param mixed $start
     * @param mixed $end
     * @return self
     */
    public function between($field, $start, $end)
    {
        $startParam = $field . '_between_start_' . count($this->lastParams);
        $endParam = $field . '_between_end_' . count($this->lastParams);
        
        $condition = "{$field} BETWEEN :{$startParam} AND :{$endParam}";
        
        if (!empty($this->lastWhere)) {
            $this->lastWhere .= " AND " . $condition;
        } else {
            $this->lastWhere = "WHERE " . $condition;
        }
        
        $this->lastParams[$startParam] = $start;
        $this->lastParams[$endParam] = $end;
        return $this;
    }

    /**
     * Add WHERE NOT BETWEEN condition
     * @param string $field
     * @param mixed $start
     * @param mixed $end
     * @return self
     */
    public function notBetween($field, $start, $end)
    {
        $startParam = $field . '_not_between_start_' . count($this->lastParams);
        $endParam = $field . '_not_between_end_' . count($this->lastParams);
        
        $condition = "{$field} NOT BETWEEN :{$startParam} AND :{$endParam}";
        
        if (!empty($this->lastWhere)) {
            $this->lastWhere .= " AND " . $condition;
        } else {
            $this->lastWhere = "WHERE " . $condition;
        }
        
        $this->lastParams[$startParam] = $start;
        $this->lastParams[$endParam] = $end;
        return $this;
    }

    // ===== QUERY BUILDER - GROUP BY & HAVING =====

    /**
     * Add GROUP BY clause
     * @param string|array $fields
     * @return self
     */
    public function groupBy($fields)
    {
        if (is_array($fields)) {
            $this->lastGroupBy = "GROUP BY " . implode(', ', $fields);
        } else {
            $this->lastGroupBy = "GROUP BY {$fields}";
        }
        return $this;
    }

    /**
     * Add HAVING clause
     * @param string $condition
     * @return self
     */
    public function having($condition)
    {
        $this->lastHaving = "HAVING {$condition}";
        return $this;
    }

    // ===== QUERY BUILDER - ORDER BY & LIMIT =====

    /**
     * Order by for query builder
     * @param string $field
     * @param string $direction
     * @return self
     */
    public function orderBy($field, $direction = 'ASC')
    {
        if (!empty($this->lastOrder)) {
            $this->lastOrder .= ", {$field} {$direction}";
        } else {
            $this->lastOrder = "ORDER BY {$field} {$direction}";
        }
        return $this;
    }

    /**
     * Add random order
     * @return self
     */
    public function orderByRandom()
    {
        $this->lastOrder = "ORDER BY RAND()";
        return $this;
    }

    /**
     * Order by latest (descending by created_at or specified field)
     * @param string|null $field
     * @return self
     */
    public function latest($field = null)
    {
        $field = $field ?: $this->createdField;
        return $this->orderBy($field, 'DESC');
    }

    /**
     * Order by oldest (ascending by created_at or specified field)
     * @param string|null $field
     * @return self
     */
    public function oldest($field = null)
    {
        $field = $field ?: $this->createdField;
        return $this->orderBy($field, 'ASC');
    }

    /**
     * Limit for query builder
     * @param int $limit
     * @param int $offset
     * @return self
     */
    public function limit($limit, $offset = 0)
    {
        $this->lastLimit = "LIMIT {$limit}";
        if ($offset > 0) {
            $this->lastLimit .= " OFFSET {$offset}";
        }
        return $this;
    }

    /**
     * Take (alias for limit)
     * @param int $count
     * @return self
     */
    public function take($count)
    {
        return $this->limit($count);
    }

    /**
     * Skip (set offset)
     * @param int $count
     * @return self
     */
    public function skip($count)
    {
        return $this->limit(PHP_INT_MAX, $count);
    }

    // ===== QUERY EXECUTION METHODS =====

    /**
     * Get first record
     * @return array|null
     */
    public function first()
    {
        $sql = $this->buildSelectQuery() . " LIMIT 1";
        $result = $this->query($sql, $this->lastParams);
        $this->resetQueryBuilder();
        
        if (!empty($result)) {
            return $this->processResult($result[0]);
        }
        
        return null;
    }

    /**
     * Get all records
     * @return array
     */
    public function get()
    {
        $sql = $this->buildSelectQuery();
        
        if (class_exists('System\Core\DebugToolbar') && DebugToolbar::isEnabled()) {
            $builderInfo = [
                'select' => $this->lastSelect,
                'where' => $this->lastWhere,
                'joins' => $this->lastJoin,
                'order' => $this->lastOrder,
                'limit' => $this->lastLimit
            ];
        }
        
        $result = $this->query($sql, $this->lastParams);
        $this->resetQueryBuilder();
        
        return array_map([$this, 'processResult'], $result);
    }

    /**
     * Find record by ID
     * @param mixed $id
     * @return array|null
     */
    public function find($id)
    {
        $this->triggerCallback('beforeFind', $id);
        $result = $this->where([$this->primaryKey => $id])->first();
        $this->triggerCallback('afterFind', $result);
        return $result;
    }

    /**
     * Find multiple records by IDs
     * @param array $ids
     * @return array
     */
    public function findAll($ids)
    {
        return $this->whereIn($this->primaryKey, $ids)->get();
    }

    /**
     * Find or fail (throws exception if not found)
     * @param mixed $id
     * @return array
     * @throws \Exception
     */
    public function findOrFail($id)
    {
        $result = $this->find($id);
        
        if ($result === null) {
            throw new \Exception("Record with ID {$id} not found in table {$this->table}");
        }
        
        return $result;
    }

    /**
     * First or fail
     * @return array
     * @throws \Exception
     */
    public function firstOrFail()
    {
        $result = $this->first();
        
        if ($result === null) {
            throw new \Exception("No records found in table {$this->table}");
        }
        
        return $result;
    }

    /**
     * Get single value from a column
     * @param string $column
     * @return mixed
     */
    public function value($column)
    {
        $result = $this->select($column)->first();
        return $result[$column] ?? null;
    }

    /**
     * Get array of values from a column
     * @param string $column
     * @param string|null $key
     * @return array
     */
    public function pluck($column, $key = null)
    {
        $results = $this->get();
        
        if ($key === null) {
            return array_column($results, $column);
        }
        
        $plucked = [];
        foreach ($results as $row) {
            $plucked[$row[$key]] = $row[$column];
        }
        
        return $plucked;
    }

    // ===== CHUNK PROCESSING =====

    /**
     * Process records in chunks
     * @param int $size Chunk size
     * @param callable $callback Callback function
     * @return bool
     */
    public function chunk($size, $callback)
    {
        $page = 1;
        
        do {
            $results = $this->limit($size, ($page - 1) * $size)->get();
            
            if (empty($results)) {
                break;
            }
            
            if ($callback($results, $page) === false) {
                return false;
            }
            
            $page++;
            
        } while (count($results) === $size);
        
        return true;
    }

    /**
     * Process each record individually
     * @param callable $callback
     * @return bool
     */
    public function each($callback)
    {
        return $this->chunk(100, function($records) use ($callback) {
            foreach ($records as $record) {
                if ($callback($record) === false) {
                    return false;
                }
            }
        });
    }

    // ===== BATCH OPERATIONS =====

    /**
     * Insert multiple records
     * @param array $data Array of arrays containing data
     * @return bool|int Number of inserted records or false on failure
     */
    public function insertBatch($data)
    {
        if (empty($data) || !is_array($data)) {
            return false;
        }

        $this->triggerCallback('beforeInsert', $data);

        $firstRow = reset($data);
        $columns = array_keys($firstRow);
        $placeholders = [];
        $values = [];
        $paramCounter = 0;

        foreach ($data as $row) {
            if ($this->useTimestamps) {
                $row[$this->createdField] = date('Y-m-d H:i:s');
                $row[$this->updatedField] = date('Y-m-d H:i:s');
            }

            $rowPlaceholders = [];
            foreach ($columns as $column) {
                $paramName = "batch_{$paramCounter}_{$column}";
                $rowPlaceholders[] = ":{$paramName}";
                $values[$paramName] = $row[$column] ?? null;
            }
            $placeholders[] = '(' . implode(', ', $rowPlaceholders) . ')';
            $paramCounter++;
        }

        $columnsStr = implode(', ', $columns);
        $valuesStr = implode(', ', $placeholders);
        $sql = "INSERT INTO {$this->table} ({$columnsStr}) VALUES {$valuesStr}";

        try {
            $result = $this->executeQuery($sql, $values);
            $this->triggerCallback('afterInsert', $data);
            return $result ? count($data) : false;
        } catch (\Exception $e) {
            $this->logError("Batch insert failed: " . $e->getMessage(), $sql);
            return false;
        }
    }

    /**
     * Update multiple records
     * @param array $data Array of arrays containing data with ID
     * @param string $indexField Field to use as index (default: primary key)
     * @return bool|int Number of updated records or false on failure
     */
    public function updateBatch($data, $indexField = null)
    {
        if (empty($data) || !is_array($data)) {
            return false;
        }

        $indexField = $indexField ?: $this->primaryKey;
        $this->triggerCallback('beforeUpdate', $data);

        $firstRow = reset($data);
        $columns = array_keys($firstRow);
        $columns = array_filter($columns, fn($col) => $col !== $indexField);

        if ($this->useTimestamps && in_array($this->updatedField, $columns)) {
            foreach ($data as &$row) {
                $row[$this->updatedField] = date('Y-m-d H:i:s');
            }
        }

        $updateCount = 0;
        foreach ($data as $row) {
            if (!isset($row[$indexField])) continue;

            $indexValue = $row[$indexField];
            unset($row[$indexField]);

            if ($this->update($this->table, $row, [$indexField => $indexValue])) {
                $updateCount++;
            }
        }

        $this->triggerCallback('afterUpdate', $data);
        return $updateCount > 0 ? $updateCount : false;
    }

    // ===== UPSERT & REPLACE =====

    /**
     * Insert or update record (upsert)
     * @param array $data Data to insert/update
     * @param array $uniqueFields Fields to check for existence
     * @return bool|string
     */
    public function upsert($data, $uniqueFields = [])
    {
        if (empty($uniqueFields)) {
            $uniqueFields = [$this->primaryKey];
        }

        $conditions = [];
        foreach ($uniqueFields as $field) {
            if (isset($data[$field])) {
                $conditions[$field] = $data[$field];
            }
        }

        $existing = $this->where($conditions)->first();

        if ($existing) {
            return $this->update($this->table, $data, $conditions);
        } else {
            return $this->insert($this->table, $data);
        }
    }

    /**
     * Update or insert (first match found will be updated)
     * @param array $conditions
     * @param array $data
     * @return bool|string
     */
    public function updateOrInsert($conditions, $data)
    {
        $existing = $this->where($conditions)->first();

        if ($existing) {
            return $this->update($this->table, $data, $conditions);
        } else {
            $insertData = array_merge($conditions, $data);
            return $this->insert($this->table, $insertData);
        }
    }

    // ===== SOFT DELETES =====

    /**
     * Soft delete record(s)
     * @param mixed $id Single ID or array of IDs
     * @return bool
     */
    public function softDelete($id)
    {
        if (!$this->useSoftDeletes) {
            return $this->delete($this->table, [$this->primaryKey => $id]);
        }

        $this->triggerCallback('beforeDelete', $id);
        
        $data = [$this->deletedField => date('Y-m-d H:i:s')];
        if ($this->useTimestamps) {
            $data[$this->updatedField] = date('Y-m-d H:i:s');
        }

        $result = is_array($id) 
            ? $this->whereIn($this->primaryKey, $id)->updateBatch($data)
            : $this->update($this->table, $data, [$this->primaryKey => $id]);

        $this->triggerCallback('afterDelete', $id);
        return $result;
    }

    /**
     * Restore soft deleted record(s)
     * @param mixed $id Single ID or array of IDs
     * @return bool
     */
    public function restore($id)
    {
        if (!$this->useSoftDeletes) {
            return false;
        }

        $data = [$this->deletedField => null];
        if ($this->useTimestamps) {
            $data[$this->updatedField] = date('Y-m-d H:i:s');
        }

        return is_array($id)
            ? $this->whereIn($this->primaryKey, $id)->updateBatch($data)
            : $this->update($this->table, $data, [$this->primaryKey => $id]);
    }

    /**
     * Include soft deleted records in query
     * @return self
     */
    public function withDeleted()
    {
        return $this;
    }

    /**
     * Get only soft deleted records
     * @return self
     */
    public function onlyDeleted()
    {
        if ($this->useSoftDeletes) {
            $this->whereNotNull($this->deletedField);
        }
        return $this;
    }

    /**
     * Force delete (permanent delete even with soft deletes)
     * @param mixed $id
     * @return bool
     */
    public function forceDelete($id)
    {
        $conditions = is_array($id) 
            ? [$this->primaryKey . ' IN' => $id]
            : [$this->primaryKey => $id];
            
        return $this->delete($this->table, $conditions);
    }

    // ===== SEARCH & FILTER METHODS =====

    /**
     * Global search across multiple fields
     * @param string $term Search term
     * @param array $fields Fields to search in
     * @return self
     */
    public function search($term, $fields = [])
    {
        if (empty($fields) || empty($term)) {
            return $this;
        }

        $conditions = [];
        $escapedTerm = addslashes($term);
        
        foreach ($fields as $field) {
            $conditions[] = "{$field} LIKE '%{$escapedTerm}%'";
        }

        $searchCondition = '(' . implode(' OR ', $conditions) . ')';
        
        if (!empty($this->lastWhere)) {
            $this->lastWhere .= " AND " . $searchCondition;
        } else {
            $this->lastWhere = "WHERE " . $searchCondition;
        }

        return $this;
    }

    /**
     * Apply filters to query
     * @param array $filters
     * @return self
     */
    public function filter($filters)
    {
        foreach ($filters as $field => $value) {
            if ($value !== null && $value !== '') {
                if (is_array($value)) {
                    $this->whereIn($field, $value);
                } else {
                    $this->where([$field => $value]);
                }
            }
        }
        return $this;
    }

    // ===== MODEL SCOPES =====

    /**
     * Apply a scope to the query
     * @param string $scope Scope name
     * @param mixed ...$args Additional arguments
     * @return self
     */
    public function scope($scope, ...$args)
    {
        $method = 'scope' . ucfirst($scope);
        
        if (method_exists($this, $method)) {
            return $this->$method(...$args);
        }
        
        return $this;
    }

    // ===== RELATIONSHIPS =====

    /**
     * Define hasOne relationship
     * @param string $related Related model class
     * @param string|null $foreignKey Foreign key in related table
     * @param string|null $localKey Local key in this table
     * @return array|null
     */
    protected function hasOne($related, $foreignKey = null, $localKey = null)
    {
        $localKey = $localKey ?: $this->primaryKey;
        $foreignKey = $foreignKey ?: strtolower($this->table) . '_id';
        
        $relatedModel = new $related();
        $localValue = $this->getData($localKey);
        
        if ($localValue === null) {
            return null;
        }
        
        return $relatedModel->where([$foreignKey => $localValue])->first();
    }

    /**
     * Define hasMany relationship
     * @param string $related Related model class
     * @param string|null $foreignKey Foreign key in related table
     * @param string|null $localKey Local key in this table
     * @return array
     */
    protected function hasMany($related, $foreignKey = null, $localKey = null)
    {
        $localKey = $localKey ?: $this->primaryKey;
        $foreignKey = $foreignKey ?: strtolower($this->table) . '_id';
        
        $relatedModel = new $related();
        $localValue = $this->getData($localKey);
        
        if ($localValue === null) {
            return [];
        }
        
        return $relatedModel->where([$foreignKey => $localValue])->get();
    }

    /**
     * Define belongsTo relationship
     * @param string $related Related model class
     * @param string|null $foreignKey Foreign key in this table
     * @param string|null $ownerKey Primary key in related table
     * @return array|null
     */
    protected function belongsTo($related, $foreignKey = null, $ownerKey = null)
    {
        $relatedModel = new $related();
        $ownerKey = $ownerKey ?: $relatedModel->primaryKey;
        $foreignKey = $foreignKey ?: strtolower($relatedModel->table) . '_id';
        
        $foreignValue = $this->getData($foreignKey);
        
        if ($foreignValue === null) {
            return null;
        }
        
        return $relatedModel->where([$ownerKey => $foreignValue])->first();
    }

    /**
     * Simple belongsToMany relationship
     * @param string $related Related model class
     * @param string $pivotTable Pivot table name
     * @param string|null $foreignPivotKey Foreign key for this model in pivot
     * @param string|null $relatedPivotKey Foreign key for related model in pivot
     * @return array
     */
    protected function belongsToMany($related, $pivotTable, $foreignPivotKey = null, $relatedPivotKey = null)
    {
        $relatedModel = new $related();
        $foreignPivotKey = $foreignPivotKey ?: strtolower($this->table) . '_id';
        $relatedPivotKey = $relatedPivotKey ?: strtolower($relatedModel->table) . '_id';
        
        $localValue = $this->getData($this->primaryKey);
        
        if ($localValue === null) {
            return [];
        }
        
        return $relatedModel
            ->join($pivotTable, "{$pivotTable}.{$relatedPivotKey} = {$relatedModel->table}.{$relatedModel->primaryKey}")
            ->where(["{$pivotTable}.{$foreignPivotKey}" => $localValue])
            ->get();
    }

    // ===== ADVANCED PAGINATION =====

    /**
     * Enhanced pagination with metadata
     * @param int $perPage Items per page
     * @param int $page Current page
     * @return array
     */
    public function paginateAdvanced($perPage = 15, $page = 1)
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        
        $countSql = $this->buildSelectQuery();
        $countSql = preg_replace('/SELECT .* FROM/', 'SELECT COUNT(*) as total FROM', $countSql, 1);
        $countSql = preg_replace('/ORDER BY .*/', '', $countSql);
        $countSql = preg_replace('/LIMIT .*/', '', $countSql);
        
        $countResult = $this->query($countSql, $this->lastParams);
        $total = $countResult[0]['total'] ?? 0;
        
        $results = $this->limit($perPage, $offset)->get();
        
        $lastPage = ceil($total / $perPage);
        
        return [
            'data' => $results,
            'pagination' => [
                'total' => (int)$total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => $lastPage,
                'from' => $offset + 1,
                'to' => min($offset + $perPage, $total),
                'has_more_pages' => $page < $lastPage,
                'has_previous_page' => $page > 1,
                'next_page' => $page < $lastPage ? $page + 1 : null,
                'previous_page' => $page > 1 ? $page - 1 : null
            ]
        ];
    }

    // ===== DATA PROCESSING =====

    /**
     * Process single result (apply casts, hide fields, append attributes)
     * @param array $data
     * @return array
     */
    protected function processResult($data)
    {
        if (empty($data)) {
            return $data;
        }

        foreach ($this->casts as $key => $type) {
            if (array_key_exists($key, $data)) {
                $data[$key] = $this->castAttribute($key, $data[$key], $type);
            }
        }

        foreach ($this->hidden as $field) {
            unset($data[$field]);
        }

        foreach ($this->appends as $attribute) {
            $method = 'get' . str_replace('_', '', ucwords($attribute, '_')) . 'Attribute';
            if (method_exists($this, $method)) {
                $data[$attribute] = $this->$method($data);
            }
        }

        return $data;
    }

    /**
     * Cast attribute to specified type
     * @param string $key
     * @param mixed $value
     * @param string $type
     * @return mixed
     */
    protected function castAttribute($key, $value, $type)
    {
        if ($value === null) {
            return null;
        }

        switch ($type) {
            case 'int':
            case 'integer':
                return (int)$value;
            
            case 'real':
            case 'float':
            case 'double':
                return (float)$value;
            
            case 'string':
                return (string)$value;
            
            case 'bool':
            case 'boolean':
                return (bool)$value;
            
            case 'array':
            case 'json':
                return is_string($value) ? json_decode($value, true) : $value;
            
            case 'object':
                return is_string($value) ? json_decode($value) : $value;
            
            case 'date':
            case 'datetime':
                return $value;
            
            default:
                return $value;
        }
    }

    /**
     * Get data attribute (for use in accessors)
     * @param string $key
     * @return mixed
     */
    protected function getData($key)
    {
        return $this->resultArray[$key] ?? null;
    }

    // ===== MASS ASSIGNMENT PROTECTION =====

    /**
     * Fill model with data (respecting fillable/guarded)
     * @param array $data
     * @return array
     */
    protected function fill($data)
    {
        if (!empty($this->fillable)) {
            return array_intersect_key($data, array_flip($this->fillable));
        }

        if ($this->guarded === ['*']) {
            return [];
        }

        return array_diff_key($data, array_flip($this->guarded));
    }

    /**
     * Create new record (mass assignment protected)
     * @param array $data
     * @return bool|string
     */
    public function create($data)
    {
        $fillableData = $this->fill($data);
        return $this->insert($this->table, $fillableData);
    }

    // ===== AGGREGATE METHODS =====

    /**
     * Get count of records
     * @param string $column
     * @return int
     */
    public function countRecords($column = '*')
    {
        return (int)$this->selectCount($column)->value('count');
    }

    /**
     * Get maximum value
     * @param string $column
     * @return mixed
     */
    public function max($column)
    {
        return $this->selectMax($column)->value($column . '_max');
    }

    /**
     * Get minimum value
     * @param string $column
     * @return mixed
     */
    public function min($column)
    {
        return $this->selectMin($column)->value($column . '_min');
    }

    /**
     * Get average value
     * @param string $column
     * @return mixed
     */
    public function avg($column)
    {
        return $this->selectAvg($column)->value($column . '_avg');
    }

    /**
     * Get sum of values
     * @param string $column
     * @return mixed
     */
    public function sum($column)
    {
        return $this->selectSum($column)->value($column . '_sum');
    }

    /**
     * NEW: Increment a column value
     * @param string $column
     * @param int $amount
     * @param array $extra Additional fields to update
     * @return bool
     */
    public function increment($column, $amount = 1, $extra = [])
    {
        $data = array_merge($extra, [$column => new \PDOStatement("({$column} + {$amount})")]);
        
        $sql = "UPDATE {$this->table} SET {$column} = {$column} + {$amount}";
        
        if (!empty($extra)) {
            foreach ($extra as $key => $value) {
                $sql .= ", {$key} = :{$key}";
            }
        }
        
        $sql .= " " . $this->lastWhere;
        
        return $this->executeQuery($sql, array_merge($this->lastParams, $extra));
    }

    /**
     * Decrement a column value
     * @param string $column
     * @param int $amount
     * @param array $extra
     * @return bool
     */
    public function decrement($column, $amount = 1, $extra = [])
    {
        return $this->increment($column, -$amount, $extra);
    }

    // ===== MODEL EVENTS & CALLBACKS =====

    /**
     * Add callback
     * @param string $event
     * @param callable $callback
     * @return void
     */
    public function addCallback($event, $callback)
    {
        if (isset($this->callbacks[$event])) {
            $this->callbacks[$event][] = $callback;
        }
    }

    /**
     * Trigger callback
     * @param string $event
     * @param mixed $data
     * @return void
     */
    protected function triggerCallback($event, &$data = null)
    {
        if (isset($this->callbacks[$event])) {
            foreach ($this->callbacks[$event] as $callback) {
                if (is_callable($callback)) {
                    call_user_func_array($callback, [&$data]);
                }
            }
        }
    }

    // ===== HELPER METHODS =====

    /**
     * Build SELECT query from current state
     * @return string
     */
    protected function buildSelectQuery()
    {
        $sql = "SELECT {$this->lastSelect} FROM {$this->table}";
        $sql .= $this->lastJoin;
        $sql .= " " . $this->lastWhere;
        $sql .= " " . $this->lastGroupBy;
        $sql .= " " . $this->lastHaving;
        $sql .= " " . $this->lastOrder;
        $sql .= " " . $this->lastLimit;
        
        return trim($sql);
    }

    /**
     * Reset query builder state
     * @return void
     */
    protected function resetQueryBuilder()
    {
        $this->lastWhere = '';
        $this->lastParams = [];
        $this->lastOrder = '';
        $this->lastLimit = '';
        $this->lastSelect = '*';
        $this->lastJoin = '';
        $this->lastGroupBy = '';
        $this->lastHaving = '';
    }

    /**
     * Enhanced buildWhereClause with OR support
     * @param array $conditions
     * @param string $operator
     * @return array
     */
    protected function buildWhereClause($conditions, $operator = 'AND')
    {
        if (empty($conditions)) {
            return ['sql' => '', 'params' => []];
        }

        $whereParts = [];
        $params = [];
        $paramCounter = count($this->lastParams);

        foreach ($conditions as $key => $value) {
            $paramCounter++;
            
            $conditionOperator = '=';
            $field = $key;
            
            if (strpos($key, '!=') !== false) {
                $parts = explode('!=', $key);
                $field = trim($parts[0]);
                $conditionOperator = '!=';
            } elseif (strpos($key, '>=') !== false) {
                $parts = explode('>=', $key);
                $field = trim($parts[0]);
                $conditionOperator = '>=';
            } elseif (strpos($key, '<=') !== false) {
                $parts = explode('<=', $key);
                $field = trim($parts[0]);
                $conditionOperator = '<=';
            } elseif (strpos($key, '>') !== false) {
                $parts = explode('>', $key);
                $field = trim($parts[0]);
                $conditionOperator = '>';
            } elseif (strpos($key, '<') !== false) {
                $parts = explode('<', $key);
                $field = trim($parts[0]);
                $conditionOperator = '<';
            } elseif (strpos($key, '<>') !== false) {
                $parts = explode('<>', $key);
                $field = trim($parts[0]);
                $conditionOperator = '<>';
            } elseif (strpos($key, ' IS NOT') !== false) {
                $field = str_replace(' IS NOT', '', $key);
                $conditionOperator = 'IS NOT';
            } elseif (strpos($key, ' IS') !== false) {
                $field = str_replace(' IS', '', $key);
                $conditionOperator = 'IS';
            }
            
            $cleanField = preg_replace('/[^a-zA-Z0-9_]/', '_', $field);
            $cleanOperator = preg_replace('/[^a-zA-Z0-9_]/', '_', $conditionOperator);
            $paramName = $cleanField . '_' . $cleanOperator . '_' . $paramCounter;
            
            if ($conditionOperator === 'IS' || $conditionOperator === 'IS NOT') {
                $whereParts[] = "{$field} {$conditionOperator} " . ($value === null ? 'NULL' : $value);
            } else {
                $whereParts[] = "{$field} {$conditionOperator} :{$paramName}";
                $params[$paramName] = $value;
            }
        }

        return [
            'sql' => "WHERE " . implode(" {$operator} ", $whereParts),
            'params' => $params
        ];
    }

    // ===== ENHANCED INSERT/UPDATE WITH CALLBACKS =====

    /**
     * Enhanced insert with callbacks and timestamps
     * @param string $table
     * @param array $data
     * @return bool|string
     */
    public function insert($table, $data)
    {
        $this->triggerCallback('beforeInsert', $data);
        
        if ($this->useTimestamps) {
            $data[$this->createdField] = date('Y-m-d H:i:s');
            $data[$this->updatedField] = date('Y-m-d H:i:s');
        }

        $columns = implode(", ", array_keys($data));
        $placeholders = ":" . implode(", :", array_keys($data));
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        
        try {
            $stmt = $this->prepare($sql);
            $result = $stmt->execute($data);
            
            if ($result) {
                $insertId = $this->lastInsertId();
                $this->triggerCallback('afterInsert', ['id' => $insertId, 'data' => $data]);
                return $insertId;
            }
            return false;
        } catch (\PDOException $e) {
            $this->logError("Insert failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Enhanced update with callbacks and timestamps
     * @param string $table
     * @param array $data
     * @param array $conditions
     * @return bool
     */
    public function update($table, $data, $conditions)
    {
        $this->triggerCallback('beforeUpdate', $data);
        
        if ($this->useTimestamps) {
            $data[$this->updatedField] = date('Y-m-d H:i:s');
        }

        $setClause = implode(", ", array_map(fn($key) => "{$key} = :{$key}", array_keys($data)));
        $where = $this->buildWhereClause($conditions);
        $sql = "UPDATE {$table} SET {$setClause} {$where['sql']}";
        $params = array_merge($data, $where['params']);
        
        try {
            $stmt = $this->prepare($sql);
            $result = $stmt->execute($params);
            $this->triggerCallback('afterUpdate', $data);
            return $result;
        } catch (\PDOException $e) {
            $this->logError("Update failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Enhanced delete with callbacks
     * @param string $table
     * @param array $conditions
     * @return bool
     */
    public function delete($table, $conditions)
    {
        $this->triggerCallback('beforeDelete', $conditions);
        
        $where = $this->buildWhereClause($conditions);
        $sql = "DELETE FROM {$table} {$where['sql']}";
        
        try {
            $stmt = $this->prepare($sql);
            $result = $stmt->execute($where['params']);
            $this->triggerCallback('afterDelete', $conditions);
            return $result;
        } catch (\PDOException $e) {
            $this->logError("Delete failed: " . $e->getMessage());
            return false;
        }
    }

    // ===== ORIGINAL METHODS =====

    protected function logError($message, $sql = null)
    {
        $logDir = __DIR__ . "/../writable/logs";
        date_default_timezone_set("Asia/Tashkent");
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }
        $logFile = $logDir . "/error_" . date("Y-m-d") . ".log";
        $dateTime = date("Y-m-d H:i:s");
        $logMessage = "[{$dateTime}] ERROR: {$message}";
        if ($sql) {
            $logMessage .= " | SQL: {$sql}";
        }
        $logMessage .= "\n";
        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }

    public function prepare($sql, $params = [])
    {
        try {
            return $this->db->getConnection()->prepare($sql);
        } catch (\PDOException $e) {
            $this->logError("Query failed: " . $e->getMessage());
            throw new \Exception("An error occurred while executing the request.");
        }
    }
    
    public function prepareAndExecute($sql, $params = [])
    {
        try {
            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (\PDOException $e) {
            $this->logError("Query failed: " . $e->getMessage(), $sql);
            throw new \Exception("An error occurred while executing the request.");
        }
    }

    protected function executeQuery($sql, $params = [])
    {
        try {
            $stmt = $this->db->getConnection()->prepare($sql);
            return $stmt->execute($params);
        } catch (\PDOException $e) {
            $this->logError("Query failed: " . $e->getMessage(), $sql);
            error_log("PDO ERROR: " . $e->getMessage());
            return false;
        }
    }

    public function findById($table, $id)
    {
        $sql = "SELECT * FROM {$table} WHERE id = :id LIMIT 1";
        $params = ['id' => $id];
        $stmt = $this->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function exists($table, $conditions)
    {
        $where = $this->buildWhereClause($conditions);
        $sql = "SELECT COUNT(*) as count FROM {$table} {$where['sql']}";
        
        $stmt = $this->prepare($sql);
        try {
            $stmt->execute($where['params']);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $result['count'] > 0;
        } catch (\PDOException $e) {
            $this->logError("Exists check failed: " . $e->getMessage() . " | SQL: " . $sql);
            return false;
        }
    }

    public function count($table, $conditions = [])
    {
        $where = $this->buildWhereClause($conditions);
        $sql = "SELECT COUNT(*) as count FROM {$table} {$where['sql']}";
        $stmt = $this->prepare($sql);
        try {
            $stmt->execute($where['params']);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return (int)$result['count'];
        } catch (\PDOException $e) {
            $this->logError("Count failed: " . $e->getMessage());
            return 0;
        }
    }

    public function paginate($table, $limit, $offset)
    {
        $sql = "SELECT * FROM {$table} LIMIT :limit OFFSET :offset";
        $stmt = $this->prepare($sql);
        try {
            $stmt->bindParam(':limit', $limit, \PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            $this->logError("Pagination failed: " . $e->getMessage());
            return [];
        }
    }

    public function getNumRows(): int
    {
        if (is_int($this->numRows)) {
            return $this->numRows;
        }
        if ($this->resultArray !== []) {
            return $this->numRows = count($this->resultArray);
        }
        if ($this->resultObject !== []) {
            return $this->numRows = count($this->resultObject);
        }

        return $this->numRows = count($this->getResultArray());
    }

    public function getResultArray()
    {
        return $this->resultArray;
    }

    private function isValidResultId(): bool
    {
        return is_resource($this->resultID) || is_object($this->resultID);
    }

    private function escapeString($str)
    {
        $str = str_replace('\\', '\\\\', $str);
        $str = str_replace('%', '\\%', $str);
        $str = str_replace('_', '\\_', $str);
        return $str;
    }

    protected function query($sql, $params = [])
    {
        date_default_timezone_set("Asia/Tashkent");
        try {
            $stmt = $this->db->getConnection()->prepare($sql);
            $result = $stmt->execute($params);

            if (stripos(trim($sql), 'SELECT') === 0) {
                return $stmt->fetchAll(\PDO::FETCH_ASSOC);
            }

            return $result;
        } catch (\PDOException $e) {
            $this->logError("Query failed: " . $e->getMessage(), $sql);
            throw new \Exception("An error occurred while executing the request.");
        }
    }

    public function queryAll($sql, array $params = [], array $types = [])
    {
        $stmt = $this->db->prepare($sql);

        foreach ($params as $key => $value) {
            $paramType = \PDO::PARAM_STR; 

            if (isset($types[$key])) {
                $paramType = $types[$key];
            } else {
                if (is_int($value)) {
                    $paramType = \PDO::PARAM_INT;
                }
            }

            $stmt->bindValue(':' . $key, $value, $paramType);
        }

        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    protected function queryWithTypes($sql, $params = [], $types = [])
    {
        try {
            $stmt = $this->db->getConnection()->prepare($sql);

            foreach ($params as $key => $value) {
                $type = isset($types[$key]) ? $types[$key] : \PDO::PARAM_STR;

                $stmt->bindValue(is_string($key) ? ":$key" : $key, $value, $type);
            }

            $stmt->execute();

            if (stripos(trim($sql), 'SELECT') === 0) {
                return $stmt->fetchAll(\PDO::FETCH_ASSOC);
            }

            return true;
        } catch (\PDOException $e) {
            $this->logError("Query failed: " . $e->getMessage(), $sql);
            throw new \Exception("An error occurred while executing the query.");
        }
    }

    protected function createTableIfNotExists($tableName, $schema)
    {
        try {
            $createTableSQL = "CREATE TABLE IF NOT EXISTS {$tableName} ({$schema}) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
            $this->query($createTableSQL);
        } catch (\Exception $e) {
            $this->logError("Error creating table: {$e->getMessage()}");
        }
    }

    public function all($table = null, $conditions = [], $orderBy = '')
    {
        $table = $table ?: $this->table;
        $where = $this->buildWhereClause($conditions);
        $sql = "SELECT * FROM $table {$where['sql']}" . ($orderBy ? " ORDER BY $orderBy" : '');
        return $this->query($sql, $where['params']);
    }

    public function beginTransaction()
    {
        try {
            $this->db->getConnection()->beginTransaction();
        } catch (\PDOException $e) {
            $this->logError("Transaction begin failed: " . $e->getMessage());
            throw new \Exception("An error occurred while starting the transaction.");
        }
    }

    public function commit()
    {
        try {
            $this->db->getConnection()->commit();
        } catch (\PDOException $e) {
            $this->logError("Transaction commit failed: " . $e->getMessage());
            throw new \Exception("An error occurred while confirming the transaction.");
        }
    }

    public function rollBack()
    {
        try {
            $this->db->getConnection()->rollBack();
        } catch (\PDOException $e) {
            $this->logError("Transaction rollback failed: " . $e->getMessage());
            throw new \Exception("An error occurred while canceling the transaction.");
        }
    }

    protected function lastInsertId()
    {
        return $this->db->getConnection()->lastInsertId();
    }

    public function createUser()
    {
        $data = [
            "id" => 1,
            "user_id" => "USER-67485ced924fb",
        ];
        $this->insert("users", $data);
    } 
}
?>
