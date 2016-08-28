<?php
namespace Core\Db\MySql;

/**
 * Interface Query
 */
interface Query
{
    public function select();
    public function from();
    public function where();
    public function limit();
    public function join();
    public function leftJoin();
    public function rightJoin();
    public function union();
}
