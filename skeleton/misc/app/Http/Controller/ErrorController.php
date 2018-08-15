<?php

namespace App\Http\Controller;

use DependencyInjector\DI;
use function GuzzleHttp\Psr7\stream_for;
use Tal\ServerResponse;

class ErrorController extends AbstractController
{
    public function notFound()
    {
        $response = new ServerResponse(404);
        $response->setBody(stream_for($this->htmlErrorPage(
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

    public function unexpectedError()
    {
        $response = new ServerResponse(500);
        $response->setBody(stream_for($this->htmlErrorPage(
            500,
            'Unexpected Error',
            'Whoops something went wrong!'
        )));
        return $response;
    }

    protected function htmlErrorPage($status, $title, $message)
    {
        ob_start();
        include DI::environment()->viewPath('error.php');
        return ob_get_clean();
    }
}
