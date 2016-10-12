<?php
namespace Application\Action\Http;

use Core\Request\Http\Action;

/**
 * Class Index
 */
class Index extends Action
{
    public function run()
    {
        echo get_class($this);
    }
}
