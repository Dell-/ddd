<?php
namespace Core\Request\Http;

use Core\Request\HandlerInterface;
use Core\Request\RequestException;
use Core\Request\RequestInterface;

/**
 * Class Handler
 */
class Handler implements HandlerInterface
{
    const HTTP_GET_REQUEST = 'get';

    const HTTP_POST_REQUEST = 'post';

    const HTTP_PUT_REQUEST = 'put';

    const HTTP_PATCH_REQUEST = 'patch';

    const HTTP_DELETE_REQUEST = 'delete';

    const HTTP_HEAD_REQUEST = 'head';

    const HTTP_OPTIONS_REQUEST = 'options';

    /**
     * @inheritdoc
     */
    public function handle(RequestInterface $request)
    {
        $data = [];
        $method = strtolower($_SERVER['REQUEST_METHOD']);

        switch ($method) {
            case self::HTTP_GET_REQUEST:
            case self::HTTP_OPTIONS_REQUEST:
            case self::HTTP_HEAD_REQUEST:
            case self::HTTP_DELETE_REQUEST:
                $data = $_GET;
                break;
            case self::HTTP_POST_REQUEST && !empty($_POST):
                $data = $_POST;
                break;
            case self::HTTP_PATCH_REQUEST:
            case self::HTTP_PUT_REQUEST:
                parse_str(file_get_contents('php://input'), $data);
                break;
            default:
                throw new RequestException();
        }

        return $data;
    }
}
