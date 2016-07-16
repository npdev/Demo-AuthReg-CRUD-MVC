<?php

namespace app\core;

class Controller {

    public $model;
    public $view;
    public $errors;
    public $fields = array('login' => 'Login', 'email' => 'Email', 'password' => 'Password');
    public $userId = null;
    public $currentPage = '';
    private $_auth = false;
    private static $_instance;

    private function __construct() {
        $this->model = Model::getInstance();
        if (!empty($_SESSION['user_id'])) {
            $this->userId = $_SESSION['user_id'];
            $this->_auth = \TRUE;
        }
    }

    public static function getInstance() {
        if (empty(self::$_instance)) {
            self::$_instance = new Controller();
        }
        return self::$_instance;
    }

    public function actionIndex() {
        if ($this->isAuth()) {
            $this->actionProfile();
        } else {
            $this->actionEnter();
        }
        return;
    }

    public function actionReg() {
        if ($this->isAuth()) {
            $this->errors[] = $this->model->getErrors(Model::ERR_AUTH);
            $this->actionIndex();
            return;
        }
        if (isset($_POST['login']) and !$this->validate()) {
            $this->errors[] = $this->model->getErrors(Model::ERR_REG);
            return $this->render('reg.php', 'template.php');
        }
        if (isset($_POST) && $this->validate()) {
            if ($this->model->isUserExists($_POST['login']) === \TRUE) {
                $this->errors[] = $this->model->getErrors(Model::ERR_USER_ALREADY_EXISTS);
            } else {
                $data = array(
                    'login' => $_POST['login'],
                    'email' => $_POST['email'],
                    'password' => $_POST['password'],
                );
                $this->userId = $this->model->addUser($data);
                if (!empty($this->userId) and $this->model->sendMail($data['email'])) {
                    $this->_auth = \TRUE;
                    $_SESSION['user_id'] = $this->userId;
                    Route::redirect('profile');
                    return;
                } else {
                    $this->errors[] = $this->model->getErrors(Model::ERR_REG);
                    return $this->render('reg.php', 'template.php');
                }
            }
        }
        return $this->render('reg.php', '');
    }

    public function actionEnter() {
        $this->currentPage = 'enter';
        return $this->render('auth.php', 'template.php');
    }

    public function actionAuth() {
        if ($this->isAuth()) {
            $this->errors[] = $this->model->getErrors(Model::ERR_AUTH);
            $this->actionIndex();
            return;
        }
        $this->currentPage = 'auth';
        $login = \filter_input(\INPUT_POST, 'login', \FILTER_SANITIZE_STRING);
        $password = \filter_input(\INPUT_POST, 'password', \FILTER_SANITIZE_STRING);
        if ($this->_auth === \FALSE AND isset($login) AND isset($password)) {
            if ($this->model->auth($login, $password) === \TRUE) {
                $this->_auth = \TRUE;
                $this->userId = $this->model->getUserId($login);
                $_SESSION['user_id'] = $this->userId;
                Route::redirect('profile');
                return;
            } else {
                $this->errors[] = $this->model->getErrors(Model::ERR_AUTH);
                return $this->render('auth.php', 'template.php');
            }
        }
        return $this->render('auth.php', '');
    }

    public function getUserLogin($userId = \null) {
        if (empty($userId) and $this->isAuth()) {
            $userId = $this->userId;
        }
        return $this->model->getUserLogin($userId);
    }

    public function actionLogout() {
        unset($_SESSION['user_id']);
        $this->_auth = \FALSE;
        $this->userId = \NULL;
        $this->actionIndex();
        return;
    }

    public function actionProfile() {
        if (!$this->_auth) {
            $this->errors[] = $this->model->getErrors(Model::ERR_AUTH);
            $this->actionIndex();
            return;
        }
        $this->currentPage = 'profile';
        $data = $this->model->getUsersGoods($this->userId);
        if ($data === \FALSE) {
            $this->errors[] = $this->model->getErrors(Model::ERR_DB);
        }
        return $this->render('profile.php', 'template.php', $data);
    }

    public function actionEditItem() {
        $data = array('name' => $_POST['name'],
            'description' => $_POST['description'],
            'price' => $_POST['price']);
        return $this->render('edit-item.php', '', $data);
    }

    public function actionSaveEditedItem() {
        $data = array(
            'id' => $_POST['id'],
            'name' => $_POST['name'],
            'description' => $_POST['description'],
            'price' => $_POST['price']);
        $dbAction = $this->model->editItem($data);
        if (!$dbAction) {
            $this->errors[] = $this->model->getErrors(Model::ERR_DB);
        }
        return $this->render('add-item.php', '', $data);
    }

    public function actionAllGoods() {
        $this->currentPage = 'allgoods';
        $data = $this->model->getAllGoods();
        if (!$data) {
            $this->errors[] = $this->model->getErrors();
        }
        return $this->render('all-goods.php', 'template.php', $data);
    }

    public function actionAddItem() {
        //$this->currentPage = 'additem';
        if (!empty($_POST)) {
            $data = array('name' => $_POST['name'],
                'description' => $_POST['description'],
                'price' => $_POST['price']);
            $this->model->addItem($this->userId, $data);
        }
        return $this->render('add-item.php', '', $data);
    }

    public function actionRemove($params) {
        if (!$this->_auth) {
            $this->errors[] = $this->model->getErrors(Model::ERR_AUTH);
            $this->actionIndex();
            return;
        }
        $result = $this->model->removeUsersItem($this->userId, $params['id']);
        if (!$result) {
            $this->errors[] = $this->model->getErrors();
        }
        return $this->render('profile.php', 'template.php');
    }

    public function actionViewUsersGoods($params) {
        $this->currentPage = 'viewusersgoods';
        if (!empty($params['login'])) {
            $login = $params['login'];
            $data = $this->model->getUsersGoodsByLogin($login);
            if (!$data) {
                $this->errors[] = $this->model->getErrors(Model::ERR_NO_DATA);
            }
        }
        return $this->render('users-goods.php', 'template.php', $data);
    }

    public function validate() {
        foreach ($this->fields as $field => $label) {
            if (!filter_has_var(INPUT_POST, $field) || empty($_POST[$field])) {
                $this->errors[] = $this->model->getErrors(Model::ERR_VALUE_EXIST, $label);
            }
        }
        if (preg_match('/\A[a-zA-Z]{2,15}\z/', $_POST['login']) !== 1) {
            $this->errors[] = $this->model->getErrors(Model::ERR_VALUE_FILTER, $this->fields['login']);
            return FALSE;
        }
        if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            $this->errors[] = $this->model->getErrors(Model::ERR_VALUE_FILTER, $this->fields['email']);
            return FALSE;
        }
        if (preg_match('/\A[0-9a-zA-Z]{6,10}\z/', $_POST['password']) !== 1) {
            $this->errors[] = $this->model->getErrors(Model::ERR_VALUE_FILTER, $this->fields['password']);
            return FALSE;
        }
        return TRUE;
    }

    public function render($content_view, $template_view = '', $data = NULL) {
        if (!empty($template_view)) {
            return include('app/views/' . $template_view);
        } else {
            return include('app/views/' . $content_view);
        }
    }

    public function isAuth() {
        if ($this->userId && $this->_auth) {
            return \TRUE;
        } else {
            return \FALSE;
        }
    }

}
