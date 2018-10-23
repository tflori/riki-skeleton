<?php

namespace App\Http\Controller;

use DependencyInjector\DI;
use function GuzzleHttp\Psr7\stream_for;
use Tal\ServerResponse;

class ErrorController extends AbstractController
{
    public function notFound(): ServerResponse
    {
        $response = new ServerResponse(404);
        $response->setBody(stream_for($this->buildHtmlErrorPage(
            404,
            'File Not Found',
            sprintf(
                'The requested url %s is not available on this server. ' .
                'Either you misspelled the url or you clicked on a dead link.',
                $this->request->getUri()->getPath()
            )
        )));
        return $response;
    }

    public function unexpectedError($exception = null): ServerResponse
    {
        $response = new ServerResponse(500);
        $response->setBody(stream_for($this->buildHtmlErrorPage(
            500,
            'Unexpected Error',
            'Whoops something went wrong!',
            $exception
        )));
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
        include DI::environment()->viewPath('error.php');
        return ob_get_clean();
    }
}
