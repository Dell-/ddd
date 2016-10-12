<?php
namespace Core\Request\Http;

use Core\Request\Http;

/**
 * Class Action
 */
class Action
{
    /**
     * @var Http
     */
    private $request;

    /**
     * Action constructor.
     *
     * @param Http $request
     */
    public function __construct(Http $request)
    {
        $this->request = $request;
    }
}
