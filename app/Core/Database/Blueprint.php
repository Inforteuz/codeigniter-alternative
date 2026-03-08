<?php

namespace App\Core\Database;

class Blueprint
{
    protected $table;
    protected $columns = [];
    protected $primaryKeys = [];
    protected $uniqueKeys = [];
    protected $commands = [];

    public function __construct(string $table)
    {
        $this->table = $table;
    }

    public function id($name = 'id')
    {
        return $this->addColumn('INTEGER', $name, ['PRIMARY KEY AUTOINCREMENT']);
    }

    public function string($name, $length = 255)
    {
        return $this->addColumn("VARCHAR($length)", $name);
    }

    public function text($name)
    {
        return $this->addColumn("TEXT", $name);
    }

    public function integer($name)
    {
        return $this->addColumn("INTEGER", $name);
    }

    public function boolean($name)
    {
        // SQLite uses INTEGER for booleans
        return $this->addColumn("INTEGER", $name);
    }

    public function timestamps()
    {
        $this->addColumn("DATETIME", 'created_at', ['NULL']);
        $this->addColumn("DATETIME", 'updated_at', ['NULL']);
    }

    public function timestamp($name)
    {
        return $this->addColumn("DATETIME", $name);
    }

    public function default($value)
    {
        $lastColumn = array_key_last($this->columns);
        if ($lastColumn !== null) {
            $val = is_string($value) ? "'$value'" : $value;
            $this->columns[$lastColumn]['attributes'][] = "DEFAULT $val";
        }
        return $this;
    }

    public function nullable()
    {
        $lastColumn = array_key_last($this->columns);
        if ($lastColumn !== null) {
            $this->columns[$lastColumn]['attributes'][] = 'NULL';
        }
        return $this;
    }

    public function unique($column = null)
    {
        if ($column) {
            $this->uniqueKeys[] = $column;
        } else {
            $lastColumn = array_key_last($this->columns);
            if ($lastColumn !== null) {
                $this->columns[$lastColumn]['attributes'][] = 'UNIQUE';
            }
        }
        return $this;
    }

    protected function addColumn($type, $name, $attributes = [])
    {
        $this->columns[$name] = compact('type', 'name', 'attributes');
        return $this;
    }

    public function build()
    {
        $statements = [];

        foreach ($this->columns as $column) {
            // e.g., "id INTEGER PRIMARY KEY AUTOINCREMENT"
            $attrString = implode(' ', $column['attributes']);
            // SQLite AUTOINCREMENT implies NOT NULL inherently, but generally:
            if (!in_array('NULL', $column['attributes']) && !in_array('PRIMARY KEY', $column['attributes'])) {
                $attrString = "NOT NULL $attrString";
            }
            $attrString = trim($attrString);
            $statements[] = "`{$column['name']}` {$column['type']} {$attrString}";
        }

        foreach ($this->uniqueKeys as $key) {
            $statements[] = "UNIQUE (`{$key}`)";
        }

        return implode(', ', $statements);
    }
}
