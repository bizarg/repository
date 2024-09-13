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
     * @param Pagination $pagination
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
     * @throws Exception
     */
    public function count(): int;

    /**
     * @param Model $model
     * @throws Exception
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
     * @return bool
     */
    public function exists(): bool;

    /**
     * @param array $data
     */
    public function updateAll(array $data): void;

    /**
     * @throws Exception
     */
    public function deleteAll(): void;

    /**
     * @return array
     */
    public function listIds(): array;

    /**
     * @param int $id
     * @return Model|null
     */
    public function findOrFail(int $id): ?Model;
}
