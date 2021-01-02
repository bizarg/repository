<?php

namespace Bizarg\Repository;

use Bizarg\Repository\Contract\Filter;
use Bizarg\Repository\Contract\Order;
use Bizarg\Repository\Contract\Pagination;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Application;
use Illuminate\Support\Collection;

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
     * @var null|Builder
     */
    protected ?Builder $builder = null;
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
        return $this->prepareBuilder()->paginate($pagination->limit(), [$this->table . '*'], 'page', $pagination->page());
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
        return $this->model->newQuery()->find($id);
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function store(Model $model): void
    {
        $this->validModel($model);

        $this->model = $model;
        $this->model->save();
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function delete(Model $model): void
    {
        $this->validModel($model);

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
     * @return $this
     */
    protected function sortAndLimit(): self
    {
        if ($this->order) {
            $this->sort($this->order);
        }

        if ($this->limit) {
            $this->limit($this->limit);
        }

        return $this;
    }

    /**
     * @param string $table
     * @return bool
     */
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
     * @return $this
     */
    protected function reset()
    {
        $this->filter = null;
        $this->order = null;
        $this->limit = null;

        return $this;
    }

    /**
     * @return $this
     */
    protected function queryForAggregateFunction()
    {
        $this->builder = $this->model->newQuery();

        $this->builder->select([
            $this->table . 'id',
        ]);

        if ($this->filter) {
            $this->filter($this->filter);
        }

        $this->builder = $this->model->newQuery()->whereIn($this->table . 'id', $this->builder->getQuery());

        return $this;
    }

    /**
     * @param $table
     * @return string
     */
    protected function table($table)
    {
        return trim($table, '.');
    }

    /**
     * @return Builder
     * @throws Exception
     */
    protected function prepareBuilder(): Builder
    {
        return $this->queryForAggregateFunction()->sortAndLimit()->reset()->builder;
    }

    /**
     * @param Model $model
     * @return bool
     * @throws Exception
     */
    protected function validModel(Model $model): bool
    {
        if (get_class($this->model) !== get_class($model)) {
            throw new Exception('Used not valid model.');
        }
    }
}
