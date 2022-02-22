<?php

Class DhonAuth {
    public function __construct()
	{
        require_once 'DhonJSON.php';
        $this->dhonjson = new DhonJSON;

        $this->dhonauth =& get_instance();
        $this->load = $this->dhonauth->load;
    }

    public function auth(string $db_api_name, $user_from_api = [])
    {
        $db_api = $this->load->database($db_api_name, TRUE);

        if ($db_api->table_exists('api_users')) {
            if (!isset($_SERVER['PHP_AUTH_USER'])) {
                $this->unauthorized();
            } else {
                $user = $user_from_api ? $user_from_api : $db_api->get_where('api_users', ['username' => $_SERVER['PHP_AUTH_USER']])->row_array();
                
                if (!password_verify($_SERVER['PHP_AUTH_PW'], $user['password'])) {
                    $this->unauthorized();
                }
            }
        }
    }

    private function unauthorized()
    {
        header('WWW-Authenticate: Basic realm="My Realm"');
        header('HTTP/1.0 401 Unauthorized');

        $response   = 'unauthorized';
        $status     = '401';
        $this->dhonjson->send($response, $status);
        exit;
    }
}