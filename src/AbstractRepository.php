<?php

namespace Bizarg\Repository;

use Bizarg\Repository\Contract\Filter;
use Bizarg\Repository\Contract\Order;
use Bizarg\Repository\Contract\Pagination;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Foundation\Application;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Class AbstractRepository
 * @package Bizarg\Repository
 */
abstract class AbstractRepository
{
    /**
     * @var Model
     */
    protected Model $model;
    /**
     * @var Application
     */
    protected Application $app;
    /**
     * @var Builder
     */
    protected Builder $builder;
    /**
     * @var QueryBuilder|null
     */
    protected ?QueryBuilder $queryBuilder = null;
    /**
     * @var Filter|null
     */
    protected ?Filter $filter = null;
    /**
     * @var Order|null
     */
    protected ?Order $order = null;
    /**
     * @var string
     */
    protected string $table;
    /**
     * @var int|null
     */
    protected ?int $limit = null;

    /**
     * @param Filter $filter
     * @return void
     */
    abstract protected function filter(Filter $filter): void;

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function pagination(Pagination $pagination): LengthAwarePaginator
    {
        $this->builder = $this->model->newQuery();

        $this->filterAndOrder();

        return $this->builder->paginate($pagination->limit(), [$this->table . '*'], 'page', $pagination->page());
    }

    private function call()
    {
        $this->builder = $this->model->newQuery();

        $this->filterAndOrder();

        $args = func_get_args();
        $method = $args[0];
        unset($args[0]);
        $this->builder->{$method}(...$args);
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function collection(): Collection
    {
        $this->builder = $this->model->newQuery();

        $this->filterAndOrder();

        return $this->builder->get();
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function pluck(string $value, ?string $key = null): Collection
    {
        $this->builder = $this->model->newQuery();

        $this->filterAndOrder();

        return $this->builder->pluck($value, $key);
    }

    /**
     * @inheritDoc
     */
    public function exists(): bool
    {
        $this->builder = $this->model->newQuery();

        if (!$this->filter) {
            return false;
        }

        $this->filter($this->filter);

        return $this->builder->exists();
    }

    /**
     * @inheritDoc
     */
    public function byId(int $id): ?Model
    {
        return $this->model->newQuery()->find($id);
    }

    /**
     * @inheritDoc
     */
    public function first(): ?Model
    {
        $this->builder = $this->model->newQuery();

        if ($this->filter) {
            $this->filter($this->filter);
        }

        return $this->builder->first();
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function count(): int
    {
        $this->builder = $this->model->newQuery();

        $this->filterAndOrder();

        return $this->builder->count();
    }

    /**
     * @inheritDoc
     */
    public function store(Model $model): void
    {
        $this->model = $model;
        $this->model->save();
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function delete(Model $model): void
    {
        $this->model = $model;
        $this->model->delete();
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function setFilter(?Filter $filter): self
    {
        if ($filter && get_class($this->model) . 'Filter' != get_class($filter)) {
            throw new Exception('Used not valid filter.');
        }

        $this->filter = $filter;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setOrder(?Order $order): self
    {
        $this->order = $order;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setLimit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * @throws Exception
     */
    protected function filterAndOrder(): void
    {
        if ($this->filter) {
            $this->filter($this->filter);
        }

        if ($this->order) {
            $this->sort($this->order);
        }

        if ($this->limit) {
            $this->limit($this->limit);
        }

        $this->reset();
    }

    /**
     * @param string $table
     * @return bool
     */
    protected function hasJoin(string $table): bool
    {
        $joins = null;

        if ($this->queryBuilder instanceof QueryBuilder) {
            $joins = $this->queryBuilder->joins;
        }

        if ($this->builder instanceof Builder) {
            $joins = $this->builder->getQuery()->joins;
        }

        if ($joins == null) {
            return false;
        }

        foreach ($joins as $join) {
            if ($join->table == $table) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param int $limit
     * @return void
     */
    protected function limit(int $limit): void
    {
        $this->builder->limit($limit);
    }

    /**
     * @param Order|null $order
     * @return void
     */
    protected function sort(?Order $order): void
    {
        $table = collect(explode('.', $order->field()))->first();

        if ($this->table != $table . '.') {
            $this->{'join' . ucfirst($table)}();
        }

        $this->builder->orderBy($order->field(), $order->direction());
    }

    /**
     * @return void
     */
    protected function reset(): void
    {
        $this->filter = null;
        $this->order = null;
        $this->limit = null;
    }

    /**
     * @param Filter $filter
     * @return void
     */
    protected function queryForAggregateFunction(?Filter $filter): void
    {
        $this->queryBuilder = DB::table($this->table($this->table));

        $this->queryBuilder->select([
            $this->table . 'id',
        ]);

        if ($filter) {
            $this->filter($filter);
        }

        $this->builder = $this->model->newQuery()->whereIn($this->table . 'id', $this->queryBuilder);
    }

    /**
     * @param $table
     * @return string
     */
    protected function table($table)
    {
        return trim($table, '.');
    }
}
