<?php

namespace Bizarg\Repository\Contract;

interface Order
{
    public function field(): ?string;

    public function setField(?string $field): static;

    public function direction(): ?string;

    public function setDirection(?string $direction): static;
}
