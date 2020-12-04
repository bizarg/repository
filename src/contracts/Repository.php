<?php

namespace Bizarg\Repository\Contract;

use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Interface Repository
 * @package Bizarg\Repository\Contract
 */
interface Repository
{
    /**
     * @param Pagination|null $pagination
     * @return LengthAwarePaginator
     */
    public function pagination(Pagination $pagination): LengthAwarePaginator;

    /**
     * @return Collection
     */
    public function collection(): Collection;

    /**
     * @return Model|null
     */
    public function first(): ?Model;

    /**
     * @param int $id
     * @return Model|null
     */
    public function byId(int $id): ?Model;

    /**
     * @param string $value
     * @param string|null $key
     * @return Collection
     */
    public function pluck(string $value, ?string $key = null): Collection;

    /**
     * @return int
     */
    public function count(): int;

    /**
     * @param Model $model
     */
    public function store(Model $model): void;

    /**
     * @param Model $model
     * @throws Exception
     */
    public function delete(Model $model): void;

    /**
     * @param Filter|null $filter
     * @return self
     */
    public function setFilter(?Filter $filter);

    /**
     * @param Order|null $order
     * @return self
     */
    public function setOrder(?Order $order);

    /**
     * @param int $limit
     * @return self
     */
    public function setLimit(int $limit);

    /**
     * @param string $value
     * @param string|null $key
     * @return bool
     */
    public function exists(string $value, ?string $key = null): bool;
}
