<?php

namespace Bizarg\Repository\Contract;


/**
 * Class Pagination
 * @package Bizarg\Repository\Contract
 */
interface Pagination
{
    /**
     * @return int
     */
    public function page();

    /**
     * @return int
     */
    public function offset();

    /**
     * @return int
     */
    public function limit();
}
