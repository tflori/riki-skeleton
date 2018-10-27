<?php

namespace App\Http\Controller;

use Tal\ServerResponse;

class ErrorController extends AbstractController
{
    public function notFound(): ServerResponse
    {
        return $this->error(404, 'File Not Found', sprintf(
            'The requested url %s is not available on this server. ' .
            'Either you misspelled the url or you clicked on a dead link.',
            $this->request->getUri()->getPath()
        ));
    }

    public function methodNotAllowed(array $allowedMethods)
    {
        return $this->error(405, 'Method Not Allowed', sprintf(
            'The requested method is not allowed for the resource %s.<br />' .
            'Allowed methods: %s',
            $this->request->getUri()->getPath(),
            implode(', ', $allowedMethods)
        ));
    }

    public function unexpectedError(\Throwable $exception = null): ServerResponse
    {
        return $this->error(500, 'Unexpected Error', 'Whoops something went wrong!', $exception);
    }
}
