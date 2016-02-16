<?php
namespace Core\Request\Cli;

use Core\Request\HandlerInterface;

/**
 * Class Handler
 */
class Handler implements HandlerInterface
{
    const REQUEST_ROUTE = 'route';

    /**
     * @inheritdoc
     */
    public function handle()
    {
        $data = [];
        if (isset($_SERVER['argv'])) {
            $argv = $_SERVER['argv'];
            array_shift($argv); // Remove script name

            $data[self::REQUEST_ROUTE] = array_shift($argv);
            foreach ($argv as $param) {
                if (preg_match('/^--(\w+)(=(.*))?$/', $param, $matches)) {
                    $name = $matches[1];
                    $data[$name] = isset($matches[3]) ? $matches[3] : true;
                } else {
                    $data[] = $param;
                }
            }
        }

        return $data;
    }
}
