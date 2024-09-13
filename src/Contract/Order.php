<?php

namespace Bizarg\Repository\Contract;

/**
 * Interface OrderInterface
 * @package Bizarg\Repository\Contract
 */
interface Order
{
    /**
     * @return string
     */
    public function field();

    /**
     * @param string $field
     *
     * @return $this
     */
    public function setField($field);

    /**
     * @return string
     */
    public function direction();

    /**
     * @param string $direction
     *
     * @return $this
     */
    public function setDirection($direction);
}
