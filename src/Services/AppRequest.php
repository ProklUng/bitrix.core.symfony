<?php

namespace Prokl\ServiceProvider\Services;

use Bitrix\Main\Application;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\HttpResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class AppRequest
 * @package Prokl\ServiceProvider\Services
 *
 * @since 24.05.2021 Экземпляры битриксовых Response/Request.
 */
class AppRequest
{
    /**
     * @var Request $request Объект PsrRequest.
     */
    private $request;

    /**
     * AppRequest constructor.
     */
    public function __construct()
    {
        $this->initGlobals();
        $this->request = Request::createFromGlobals();
    }

    /**
     * Объект PsrRequest.
     *
     * @return Request
     */
    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * Битриксовый Request.
     *
     * @return HttpRequest
     */
    public function bitrixRequest() : HttpRequest
    {
        return Application::getInstance()->getContext()->getRequest();
    }

    /**
     * Битриксовый Response.
     *
     * @return HttpResponse
     */
    public function bitrixResponse() : HttpResponse
    {
        return Application::getInstance()->getContext()->getResponse();
    }

    /**
     * Установить ключ в массиве $_SERVER.
     *
     * @param string $key   Ключ.
     * @param mixed  $value Значение.
     *
     * @return void
     */
    public function setServer(string $key, $value): void
    {
        $this->request->server->set($key, $value);
    }

    /**
     * DOCUMENT_ROOT.
     *
     * @return string|null
     */
    public function getDocumentRoot() : ?string
    {
        return $this->request->server->get('DOCUMENT_ROOT');
    }

    /**
     * HTTP_HOST.
     *
     * @return string|null
     */
    public function getHttpHost() : ?string
    {
        return $this->request->server->get('HTTP_HOST');
    }

    /**
     * REQUEST_URI.
     *
     * @return string|null
     */
    public function getRequestUri() : ?string
    {
        return $this->request->server->get('REQUEST_URI');
    }

    /**
     * Инициализировать супер-глобальное, если оно еще не инициализировано.
     *
     * @return void
     */
    private function initGlobals() : void
    {
        $_GET = !empty($_GET) ? $_GET : [];
        $_POST = !empty($_POST) ? $_POST : [];
        $_COOKIE = !empty($_COOKIE) ? $_COOKIE : [];
        $_FILES = !empty($_FILES) ? $_FILES : [];
        $_SERVER = !empty($_SERVER) ? $_SERVER : [];
    }
}
