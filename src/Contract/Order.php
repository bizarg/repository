<?php

namespace Bizarg\Repository\Contract;

interface Order
{
    public function fields(): array;

    public function setFields(array $fields): static;

    public function directions(): array;

    public function setDirections(array $directions): static;

    public function setCases(array $values, array $allowedFields, string $field = 'id'): static;

    public function getCases(): array;
}
