<?php

Class DhonMigrate {
    public $version;
    public $table;
    public $constraint;
    public $unique;
    public $ai;
    public $default;
    public $fields = [];

    public function __construct(string $database)
	{
        require_once 'DhonJSON.php';
        $this->dhonjson = new DhonJSON;
        
        $this->dhonmigrate =& get_instance();

        $this->database = $database;
        $this->db       = $this->dhonmigrate->load->database($database, TRUE);
        $this->dbforge  = $this->dhonmigrate->load->dbforge($this->db, TRUE);
    }

    public function constraint(string $value)
    {
        $this->constraint = $value;
        return $this;
    }

    public function unique()
    {
        $this->unique = TRUE;
        return $this;
    }

    public function ai()
    {
        $this->ai = TRUE;
        return $this;
    }

    public function default($value)
    {
        $this->default = $value;
        return $this;
    }

    public function field($field_name, string $type, string $nullable = '')
    {
        $field_data['type'] = $type;

        if ($this->constraint !== '')   $field_data['constraint']       = $this->constraint;
        if ($this->unique === TRUE)     $field_data['unique']           = $this->unique;
        if ($this->ai === TRUE)         $field_data['auto_increment']   = $this->ai;
        if ($this->default !== '')      $field_data['default']          = $this->default;
        if ($nullable === 'nullable')   $field_data['null']             = TRUE;

        if (is_array($field_name)) {
            $field_data['name'] = $field_name[1];

            $field_element = [
                $field_name[0] => $field_data
            ];
        } else {
            $field_element = [
                $field_name => $field_data
            ];
        }

        $this->fields = array_merge($this->fields, $field_element);
        $this->constraint = '';
        $this->unique = FALSE;
        $this->ai = FALSE;
        $this->default = '';
    }

    public function add_key(string $field_name)
    {
        $this->dbforge->add_key($field_name, TRUE);
    }

    public function create_table(string $force = '')
    {
        if ($this->db->table_exists($this->table)) {
            if ($force == 'force') {
                $this->dbforge->drop_table($this->table);
            } else{
                $response   = "failed";
                $status     = '304';
                $data       = ["Table `{$this->table}` exist"];
                $this->dhonjson->send($response, $status, $data);
                exit;
            }
        }
        $this->dbforge->add_field($this->fields);
        $this->dbforge->create_table($this->table);

        $this->fields = [];
    }

    public function add_field()
    {
        $this->dbforge->add_column($this->table, $this->fields);

        $this->fields = [];
    }

    public function change_field()
    {
        $this->dbforge->modify_column($this->table, $this->fields);

        $this->fields = [];
    }

    public function drop_field(string $field)
    {
        $this->dbforge->drop_column($this->table, $field);
    }

    public function insert(array $value)
    {
        $fields = $this->db->list_fields($this->table);
        $values = in_array('stamp', $fields) ? array_merge($value, ['stamp' => time()]) : $value;
        $this->db->insert($this->table, $values);
    }

    public function migrate(string $classname, string $action = '')
    {
        $path = ENVIRONMENT == 'testing' || ENVIRONMENT == 'development' ? "\\" : "/";
        require APPPATH."migrations{$path}{$this->version}_{$classname}.php";
        $migration_name = "Migration_{$classname}";
        $migration      = new $migration_name($this->database);

        $this->table = 'migrations';
        $this->constraint('20')->field('version', 'BIGINT');
        $this->create_table('force');
        $this->db->insert($this->table, ['version' => $this->version]);

        $action == 'change' ? $migration->change() : ($action == 'drop' ? $migration->drop() : $migration->up());
    }
}