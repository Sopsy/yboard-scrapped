<?php

namespace YBoard\Abstracts;

use Library\DbConnection;
use Library\HttpResponse;
use Library\TemplateEngine;
use YBoard\Models;
use YBoard;

abstract class ExtendedController extends YBoard\Controller
{
    protected $i18n;
    protected $db;

    public function __construct()
    {
        $this->loadConfig();
        $this->dbConnect();
    }

    protected function dbConnect()
    {
        $this->db = new DbConnection(require(ROOT_PATH . '/YBoard/Config/DbConnection.php'));
    }

    protected function disallowNonPost()
    {
        if (!$this->isPostRequest()) {
            HttpResponse::setStatusCode(405, ['Allowed' => 'POST']);
            $this->stopExecution();
        }
    }

    protected function isPostRequest()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            return true;
        }

        return false;
    }

    protected function invalidAjaxData()
    {
        HttpResponse::setStatusCode(401);
        $this->jsonMessage('Virheellinen pyyntö', true);
        $this->stopExecution();
    }

    protected function jsonMessage($str = '', $error = false)
    {
        echo json_encode(['error' => $error, 'message' => $str]);
    }

    protected function invalidData()
    {
        HttpResponse::setStatusCode(401);

        $view = new TemplateEngine(ROOT_PATH . '/YBoard/Views/Templates/Default.phtml');

        $view->errorTitle = 'Virheellinen pyyntö';
        $view->errorMessage = 'Pyyntöäsi ei voitu käsitellä, koska se sisälsi virheellistä tietoa. Yritä uudelleen.';

        $view->display(ROOT_PATH . '/YBoard/Views/Pages/Error.phtml');

        $this->stopExecution();
    }

    protected function validateCsrfToken($token)
    {
        if (empty($token) || empty($_SESSION['csrfToken'])) {
            return false;
        }

        if ($token == $_SESSION['csrfToken']) {
            return true;
        }

        return false;
    }

    protected function validateAjaxCsrfToken()
    {
        if (!$this->isPostRequest()) {
            $this->ajaxCsrfValidationFail();
        }

        if (empty($_SERVER['HTTP_X_CSRF_TOKEN']) || empty($_SESSION['csrfToken'])) {
            $this->ajaxCsrfValidationFail();
        }

        if ($_SERVER['HTTP_X_CSRF_TOKEN'] == $_SESSION['csrfToken']) {
            return true;
        }

        $this->ajaxCsrfValidationFail();
    }

    protected function ajaxCsrfValidationFail()
    {
        HttpResponse::setStatusCode(401);
        $this->jsonMessage('Istuntosi on vanhentunut. Ole hyvä ja päivitä tämä sivu.', true);
        $this->stopExecution();
    }

    protected function jsonError($str = '')
    {
        $this->jsonMessage($str, true);
        $this->stopExecution();
    }
}
