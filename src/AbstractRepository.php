<?php

namespace Bizarg\Repository;

use Bizarg\Repository\Contract\Filter;
use Bizarg\Repository\Contract\Order;
use Bizarg\Repository\Contract\Pagination;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Expression;
use Illuminate\Foundation\Application;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

abstract class AbstractRepository
{
    protected Model $model;
    protected Application $app;
    protected ?Builder $builder = null;
    protected ?Filter $filter = null;
    protected ?Order $order = null;
    protected string $table;
    protected ?int $limit = null;
    protected ?array $columns = null;
    protected $isAggregateQuery = false;
    private array $additionalTables = [];

    abstract protected function filter(Filter $filter): void;

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function pagination(Pagination $pagination): LengthAwarePaginator
    {
        $this->prepareBuilder();

        return $this->builder->paginate(
            perPage: $pagination->limit(),
            page: $pagination->page()
        );
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function collection(): Collection
    {
        return $this->prepareBuilder()->get();
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function pluck(string $value, ?string $key = null): Collection
    {
        return $this->prepareBuilder()->pluck($value, $key);
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function listIds(): array
    {
        return $this->pluck('id')->toArray();
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function exists(): bool
    {
        if (!$this->filter) {
            return false;
        }

        return $this->prepareBuilder()->exists();
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function first(): ?Model
    {
        return $this->prepareBuilder()->first();
    }

    /**
     * @inheritDoc
     */
    public function findOrFail(int $id): ?Model
    {
        $this->reset();
        return $this->model->newQuery()->findOrFail($id);
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function count(): int
    {
        return $this->prepareBuilder()->count();
    }

    /**
     * @inheritDoc
     */
    public function byId(int $id): ?Model
    {
        $this->reset();
        return $this->model->newQuery()->find($id);
    }

    public function getPreperedBuilder(): Builder
    {
        return $this->prepareBuilder();
    }

    public function value(string $column): mixed
    {
        return $this->prepareBuilder()->value($column);
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function store(Model $model): void
    {
        $this->validModel($model);
        $model->save();
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function delete(Model $model): void
    {
        $this->validModel($model);
        $model->delete();
    }

    /**
     * @throws Exception
     */
    public function deleteAll(): void
    {
        $this->model->newQuery()->whereIn('id', $this->listIds())->delete();
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function updateAll(array $data): void
    {
        $this->model->newQuery()->whereIn('id', $this->listIds())->update($data);
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function setFilter(?Filter $filter): static
    {
        if ($filter && get_class($this->model) . 'Filter' != get_class($filter)) {
            throw new Exception('Used not valid filter.');
        }

        $this->filter = $filter;

        return $this;
    }

    public function setColumns(array $columns): static
    {
        $this->columns = $columns;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setOrder(?Order $order): static
    {
        $this->order = $order;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setLimit(int $limit): static
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * @throws Exception
     */
    protected function sortAndLimit(): static
    {
        $this->sort($this->order);

        if ($this->limit) {
            $this->limit($this->limit);
        }

        return $this;
    }

    public function isAggregateQuery(): bool
    {
        return $this->isAggregateQuery;
    }

    public function setIsAggregateQuery(bool $isAggregateQuery): static
    {
        $this->isAggregateQuery = $isAggregateQuery;
        return $this;
    }

    public function getAdditionalTables(): array
    {
        return $this->additionalTables;
    }

    public function setAdditionalTables(array $additionalTables): static
    {
        $this->additionalTables = $additionalTables;
        return $this;
    }

    protected function hasJoin(string $table): bool
    {
        $joins = $this->builder->getQuery()->joins;

        if (is_null($joins)) {
            return false;
        }

        foreach ($joins as $join) {
            if ($join->table == $table) {
                return true;
            }
        }

        return false;
    }

    protected function limit(int $limit): void
    {
        $this->builder->limit($limit);
    }

    protected function sort(?Order $order): void
    {
        if (!$order) {
            $this->builder->orderBy($this->table . 'id');
            return;
        }

        foreach ($order->getCases() as $field => $array) {
            $this->builder->orderByRaw("CASE " .
                implode(
                    ' ',
                    array_map(
                        function ($value) use ($field) {
                            return "WHEN $field = $value THEN 0";
                        },
                        $array
                    )
                ) . " ELSE 1 END");
        }

        foreach ($order->fields() as $index => $field) {
            $this->joinTable($field);

            $this->builder->orderBy($field, $order->directions()[$index]);
        }
    }

    protected function joinTable(string $table)
    {
        if (strpos($table, '.') !== false) {
            $table = collect(explode('.', $table))->first();
        }

        if ($this->table != $table . '.') {
            $method = 'join' . Str::ucfirst(Str::camel($table));
            if (method_exists($this, $method)) {
                $this->{$method}();
            }
        }
    }

    protected function reset(): static
    {
        $this->filter = null;
        $this->order = null;
        $this->limit = null;

        return $this;
    }

    protected function queryForAggregateFunction(): static
    {
        $this->builder = $this->model->newQuery();

        if ($this->filter) {
            $this->filter($this->filter);
        }

        if ($this->isAggregateQuery) {
            $this->builder->select([
                $this->table . 'id',
            ]);

            $this->builder = $this->model->newQuery()->whereIn($this->table . 'id', $this->builder->getQuery());
        }

        if ($this->columns && count($this->columns)) {
            foreach ($this->columns as $column) {
                if ($column instanceof Expression) {
                    continue;
                }
                $this->joinTable($column);
            }
            $this->builder->select($this->columns);
            $this->columns = null;
        } else {
            $this->builder->select($this->table . '*');
        }


        foreach ($this->additionalTables as $table) {
            $this->joinTable($table);
        }

        return $this;
    }

    protected function table(string $table): string
    {
        return trim($table, '.');
    }

    /**
     * @throws Exception
     */
    protected function prepareBuilder(): Builder
    {
        return $this->queryForAggregateFunction()->sortAndLimit()->reset()->builder;
    }

    /**
     * @throws Exception
     */
    protected function validModel(Model $model): void
    {
        if (get_class($this->model) !== get_class($model)) {
            throw new Exception('Used not valid model.');
        }
    }
}
