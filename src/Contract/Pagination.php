<?php

namespace Bizarg\Repository\Contract;

interface Pagination
{
    public function page(): ?int;

    public function offset(): ?int;

    public function limit(): ?int;
}
