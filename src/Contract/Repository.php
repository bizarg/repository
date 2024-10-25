<?php

namespace Bizarg\Repository\Contract;

use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

interface Repository
{
    public function pagination(Pagination $pagination): LengthAwarePaginator;

    public function collection(): Collection;

    public function first(): ?Model;

    public function byId(int $id): ?Model;

    public function pluck(string $value, ?string $key = null): Collection;

    /**
     * @throws Exception
     */
    public function count(): int;

    /**
     * @throws Exception
     */
    public function store(Model $model): void;

    /**
     * @throws Exception
     */
    public function delete(Model $model): void;

    public function setFilter(?Filter $filter): static;

    public function setOrder(?Order $order): static;

    public function setLimit(int $limit): static;

    public function setColumns(array $columns): static;

    public function exists(): bool;

    public function updateAll(array $data): void;

    /**
     * @throws Exception
     */
    public function deleteAll(): void;

    public function listIds(): array;

    /**
     * @throws Exception
     */
    public function findOrFail(int $id): ?Model;

    public function getPreperedBuilder(): Builder;

    public function setIsAggregateQuery(bool $isAggreageQuery): static;

    public function isAggregateQuery(): bool;
}
