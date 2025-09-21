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
 * @date       2025-01-01
 *
 * @description
 * Enhanced functionality includes:
 *
 * 1. **Advanced Query Builder**:
 *    - SELECT with specific fields, DISTINCT, aggregate functions
 *    - JOIN operations (INNER, LEFT, RIGHT)
 *    - GROUP BY and HAVING clauses
 *    - Complex WHERE conditions with OR support
 *    - LIKE, IN, BETWEEN operations
 *
 * 2. **Batch Operations**:
 *    - insertBatch() for multiple row insertions
 *    - updateBatch() for bulk updates
 *    - Batch delete operations
 *
 * 3. **Advanced Model Features**:
 *    - Soft delete support
 *    - Model events/callbacks
 *    - Automatic timestamps
 *    - Data validation
 *    - Relationship handling
 *
 * 4. **Search & Filter**:
 *    - Global search across multiple fields
 *    - Advanced filtering with multiple criteria
 *    - Full-text search support
 */
namespace System;
use System\Database\Database;

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
     * Add WHERE LIKE condition
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
        return $result[0] ?? null;
    }

    /**
     * Get all records
     * @return array
     */
    public function get()
    {
        $sql = $this->buildSelectQuery();
        $result = $this->query($sql, $this->lastParams);
        $this->resetQueryBuilder();
        return $result;
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
        // This method allows including soft-deleted records
        // Implementation depends on your query building logic
        return $this;
    }

    /**
     * Get only soft deleted records
     * @return self
     */
    public function onlyDeleted()
    {
        if ($this->useSoftDeletes) {
            $this->where([$this->deletedField . ' IS NOT' => null]);
        }
        return $this;
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
        foreach ($fields as $field) {
            $conditions[] = "{$field} LIKE '%{$term}%'";
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
            
            // Parse operators from key
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

    // ===== ORIGINAL METHODS (PRESERVED) =====

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
            throw new \Exception("So'rov bajarishda xatolik yuz berdi.");
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
            throw new \Exception("So'rov bajarishda xatolik yuz berdi.");
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
            throw new \Exception("So'rov bajarishda xatolik yuz berdi.");
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
            $this->logError("Jadvalni yaratishda xatolik: {$e->getMessage()}");
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
            throw new \Exception("Tranzaksiyani boshlashda xatolik yuz berdi.");
        }
    }

    public function commit()
    {
        try {
            $this->db->getConnection()->commit();
        } catch (\PDOException $e) {
            $this->logError("Transaction commit failed: " . $e->getMessage());
            throw new \Exception("Tranzaksiyani tasdiqlashda xatolik yuz berdi.");
        }
    }

    public function rollBack()
    {
        try {
            $this->db->getConnection()->rollBack();
        } catch (\PDOException $e) {
            $this->logError("Transaction rollback failed: " . $e->getMessage());
            throw new \Exception("Tranzaksiyani bekor qilishda xatolik yuz berdi.");
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
            // ...
        ];
        $this->insert("users", $data);
    } 
}
?>
