<?php

namespace app\core;

class Route {

    public $action = '';
    public $params = array();

    public function init() {
        $controller = Controller::getInstance();
        $this->setActionAndParams();
        if (!empty($this->action)) {
            $action_name = 'action' . ucfirst(strtolower($this->action));
            if (method_exists($controller, $action_name)) {
                $controller->$action_name($this->params);
            }
        } else {
            $controller->actionIndex();
        }
        return;
    }

    public function setActionAndParams() {
        $parts = \explode('/', $_SERVER['REQUEST_URI']);
        if (!empty($parts[1])) {
            $paramPart = \explode('?', $parts[1]);
            $this->action = $paramPart[0];
            if (!empty($paramPart[1])) {
                $params = \explode('&', $paramPart[1]);
                foreach ($params as $param) {
                    $getParams = \explode('=', $param);
                    $this->params[$getParams[0]] = $getParams[1];
                }
            }
        }
        return;
    }

    static function redirect($page) {
        $host = 'http://' . $_SERVER['HTTP_HOST'] . '/' . $page;
        header('Location:' . $host);
        return;
    }

    static function ErrorPage404() {
        $host = 'http://' . $_SERVER['HTTP_HOST'] . '/';
        header('HTTP/1.1 404 Not Found');
        header("Status: 404 Not Found");
        header('Location:' . $host);
        return;
    }

}
