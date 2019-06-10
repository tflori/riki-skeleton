<?php

namespace App\Http\Controller;

use App\Application;
use function GuzzleHttp\Psr7\stream_for;
use Psr\Http\Message\ServerRequestInterface;
use Tal\ServerResponse;

abstract class AbstractController
{
    /** @var Application */
    protected $app;

    /** @var ServerRequestInterface */
    protected $request;

    /** @var string */
    protected $action;

    public function __construct(Application $app, $action = 'getIndex')
    {
        $this->app = $app;
        $this->action = $action;
    }

    /**
     * Returns a error response
     *
     * @param int $status
     * @param string $reason
     * @param string $message
     * @param \Throwable $exception
     * @return ServerResponse
     */
    protected function error(int $status, string $reason, string $message, \Throwable $exception = null): ServerResponse
    {
        $response = new ServerResponse($status);
        // @todo check the accept header and format a proper error (html/json,xml)
        $response->setBody(stream_for($this->buildHtmlErrorPage($status, $reason, $message, $exception)));
        return $response;
    }

    /**
     * Builds an error page from template error.php
     *
     * You may want to implement a template engine and replace this calls with something else.
     *
     * @param int $status
     * @param string $title
     * @param string $message
     * @param \Exception $exception
     * @return false|string
     */
    protected function buildHtmlErrorPage(int $status, string $title, string $message, $exception = null)
    {
        ob_start();
        include $this->app->environment->viewPath('error.php');
        return ob_get_clean();
    }
}
