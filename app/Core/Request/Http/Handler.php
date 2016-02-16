<?php
namespace Core\Request\Http;

use Core\Request\HandlerInterface;

/**
 * Class Handler
 */
class Handler implements HandlerInterface
{
    const HTTP_GET_REQUEST = 'get';

    const HTTP_POST_REQUEST = 'post';

    const HTTP_PUT_REQUEST = 'put';

    const HTTP_DELETE_REQUEST = 'delete';

    /**
     * @inheritdoc
     */
    public function handle()
    {
        $data = [];
        $method = strtolower($_SERVER['REQUEST_METHOD']);

        switch ($method) {
            case self::HTTP_GET_REQUEST:
                $data = $_GET;
                break;
            case self::HTTP_POST_REQUEST:
            case self::HTTP_PUT_REQUEST:
                $data = $_POST;
                break;
            case self::HTTP_DELETE_REQUEST:
                parse_str(file_get_contents('php://input'), $data);
                break;
        }

        return $data;
    }
}
