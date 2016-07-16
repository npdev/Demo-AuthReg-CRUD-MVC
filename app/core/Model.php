<?php

namespace app\core;

class Model {

    const ERR_AUTH = 1;
    const ERR_USER_ALREADY_EXISTS = 2;
    const ERR_REG = 3;
    const ERR_VALUE_EXIST = 4;
    const ERR_VALUE_FILTER = 5;
    const ERR_NO_DATA = 6;
    const ERR_DB = 9;

    public $userLogin;
    public $userPass;
    public $errors;
    private $_dbHost = "localhost";
    private $_dbName = "db_store";
    private $_dbUser = "store_admin";
    private $_dbPass = "admin";
    private $_pdo = null;
    private static $_instance;

    private function __construct($userLogin = null) {
        $this->userLogin = $userLogin;
        $this->connectDB($this->_dbHost, $this->_dbName, $this->_dbUser, $this->_dbPass);
    }

    public static function getInstance() {
        if (empty(self::$_instance)) {
            self::$_instance = new Model();
        }
        return self::$_instance;
    }

    public function connectDB($dbHost, $dbName, $dbUser, $dbPass) {
        try {
            $dsn = "mysql:host=$dbHost;dbname=$dbName";
            $opt = array(
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
            );
            $this->_pdo = new \PDO($dsn, $dbUser, $dbPass, $opt);
        } catch (\PDOException $e) {
            echo 'Connection is broken: ' . $e->getMessage();
            exit;
//            $this->errors = array('error' => $e->getMessage());
//            return;
        }
        $this->_pdo->query('set names utf8');
        return;
    }

    public function isUserExists($login) {
        $stmt = $this->_pdo->prepare('SELECT login FROM users '
                . 'WHERE login = :login');
        $result = $stmt->execute(array('login' => $login));
        if ($result === FALSE) {
            throw new \Exception(Model::ERR_DB);
        }
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!isset($data['login'])) {
            return FALSE;
        }
        return TRUE;
    }

    public function getUserId($login) {
        $stmt = $this->_pdo->prepare('SELECT id FROM users '
                . 'WHERE login = :login');
        $result = $stmt->execute(array('login' => $login));
        if (!$result)
            return \FALSE;
        $userId = $stmt->fetchColumn();
        return $userId;
    }

    public function getUserLogin($userId) {
        $stmt = $this->_pdo->prepare('SELECT login FROM users '
                . 'WHERE id = :id');
        $result = $stmt->execute(array('id' => $userId));
        if (!$result)
            return \FALSE;
        $userLogin = $stmt->fetchColumn();
        return $userLogin;
    }

    public function auth($login, $password) {
        if ($this->isUserExists($login) === \FALSE) {
            return \FALSE;
        }
        $stmt = $this->_pdo->prepare('SELECT login FROM users '
                . 'WHERE login=:login AND password=:password');
        $result = $stmt->execute(array(
            'login' => $login,
            'password' => md5($login . $password)
        ));
        $auth = $stmt->fetchColumn();
        if (!$result or empty($auth)) {
            return \FALSE;
        }
        return \TRUE;
    }

    public function addUser(array $param) {
        if (empty($param['login']) || empty($param['email']) || empty($param['password'])) {
            return FALSE;
        }
        $stmt = $this->_pdo->prepare('INSERT INTO users (login, email, password) '
                . 'VALUES (:login, :email, :password)');
        $result = $stmt->execute(array(
            'login' => $param['login'],
            'email' => $param['email'],
            'password' => md5($param['login'] . $param['password'])
        ));
        if (!$result) {
            return FALSE;
        }
        $stmt = $this->_pdo->prepare('SELECT id FROM users WHERE login=:login');
        $result = $stmt->execute(array('login' => $param['login']));
        if (!$result) {
            return FALSE;
        }
        $this->userLogin = $param['login'];
        $this->userPass = $param['password'];
        $userId = $stmt->fetchColumn();
        return $userId;
    }

    public function sendMail($email) {
        $from = "Admin";
        $subject = "Registration";
        $mail_body = "You are registrated on Store.\n"
                . "Your data:\n"
                . "Login:" . $this->userLogin . "\n"
                . "Password:" . $this->userPass;
        $header = "MIME-Version: 1.0\n";
        $header .= "Content-Type: text/plain; charset=UTF-8\n";
        $header .= "From: " . $from . " <store-admin@mail.com>";
        if (mail($email, $subject, $mail_body, $header)) {
            return true;
        }
        return false;
    }

    public function addItem($userId, $data) {
        $stmt = $this->_pdo->prepare('SELECT id FROM goods WHERE name=:name LIMIT 1');
        $result = $stmt->execute(array('name' => $data['name']));
        if (!$result) {
            return \FALSE;
        }
        $itemId = $stmt->fetchColumn();
        if ($itemId) {
            $stmt = $this->_pdo->prepare('INSERT INTO users_goods (item_id, user_id) '
                    . 'VALUE (:itemId, :userId)');
            $result = $stmt->execute(array('itemId' => $itemId, 'userId' => $userId));
            if (!$result) {
                return \FALSE;
            }
        } else {
            $stmt = $this->_pdo->prepare('INSERT INTO goods (name, description, price) '
                    . 'VALUES (:name, :description, :price)');
            $result = $stmt->execute(array(
                'name' => $data['name'],
                'description' => $data['description'],
                'price' => $data['price']
            ));
            if (!$result) {
                return \FALSE;
            }
            $query = $this->_pdo->query('SELECT id FROM goods ORDER BY id DESC LIMIT 1');
            foreach ($query as $row) {
                $itemId = $row['id'];
            }
            $stmt = $this->_pdo->prepare('INSERT INTO users_goods (item_id, user_id) '
                    . 'VALUE (:itemId, :userId)');
            $result = $stmt->execute(array('itemId' => $itemId, 'userId' => $userId));
            if (!$result) {
                return \FALSE;
            }
        }
        return \TRUE;
    }

    /* !!!Trigger is here:
     * CREATE TRIGGER `after_delete_goods` AFTER DELETE ON `goods`
      FOR EACH ROW DELETE FROM users_goods WHERE item_id = OLD.id
     */

    public function removeItem($name) {
        $stmt = $this->_pdo->prepare('DELETE FROM goods WHERE name=:name');
        $result = $stmt->execute(array('name' => $name));
        if (!$result) {
            return \FALSE;
        }
        return \TRUE;
    }

    public function removeUsersItem($userId, $itemId) {
        $stmt = $this->_pdo->prepare('DELETE FROM users_goods '
                . 'WHERE item_id=:itemId AND user_id=:userId');
        $result = $stmt->execute(array('userId' => $userId, 'itemId' => $itemId));
        if (!$result) {
            return \FALSE;
        }
        return \TRUE;
    }

    public function getItemId($itemName) {
        
    }

    public function editItem($data) {
        $stmt = $this->_pdo->prepare('UPDATE goods '
                . 'SET name=:name, description=:description, price=:price '
                . 'WHERE id=:id');
        $res = $stmt->execute($data);
        if (!$res) {
            return \FALSE;
        }
        return \TRUE;
    }

    public function getUsersGoods($userId) {
        $stmt = $this->_pdo->prepare('SELECT ug.item_id AS id, g.name, g.description, g.price '
                . 'FROM goods AS g, users_goods AS ug '
                . 'WHERE ug.user_id = :userId AND g.id = ug.item_id');
        $result = $stmt->execute(array('userId' => $userId));
        if (!$result) {
            return \FALSE;
        }
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $data[] = $row;
        }
        return $data;
    }

    public function getUsersGoodsByLogin($login) {
        $stmt = $this->_pdo->prepare('SELECT g.name, g.description, g.price '
                . 'FROM goods AS g, users_goods AS ug, users AS u '
                . 'WHERE u.login = :login AND u.id = ug.user_id AND g.id = ug.item_id');
        $result = $stmt->execute(array('login' => $login));
        if (!$result) {
            return \FALSE;
        }
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $data[] = $row;
        }
        return $data;
    }

    public function getAllGoods() {
        $query = $this->_pdo->query('SELECT name, description, price FROM goods', \PDO::FETCH_ASSOC);
        if (!$query) {
            return \FALSE;
        }
        $data = array();
        foreach ($query as $row) {
            $data[] = $row;
        }
        return $data;
    }

    public function errorHandler($errCode) {
        
    }

    public function getErrors($errCode = 0, $field = '') {
        switch ($errCode) {
            case self::ERR_AUTH : return 'Authorization error';
            case self::ERR_REG : return 'Registration error';
            case self::ERR_USER_ALREADY_EXISTS : return 'User already exists';
            case self::ERR_VALUE_EXIST : return "The field \"$field\" must be filled";
            case self::ERR_VALUE_FILTER : return "The field \"$field\" is uncorrect";
            case self::ERR_DB: return "Data base error";
            case self::ERR_NO_DATA: return "No data found in data base";
            default : return 'Undefined error';
        }
    }

}
