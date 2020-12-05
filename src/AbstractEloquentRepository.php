<?php

namespace Bizarg\Repository;

use Bizarg\Repository\Contract\Filter;
use Bizarg\Repository\Contract\Order;
use Bizarg\Repository\Contract\Pagination;
use Bizarg\Repository\Contract\Repository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Application;
use Illuminate\Support\Collection;

/**
 * Class EloquentLeadRepository
 * @package App\Infrastructure\Eloquent
 */
abstract class AbstractEloquentRepository implements Repository
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
     */
    public function pagination(Pagination $pagination): LengthAwarePaginator
    {
        $this->builder = $this->model->newQuery();

        $this->filterAndOrder();

        return $this->builder->paginate($pagination->limit(), ['*'], 'page', $pagination->page());
    }

    /**
     * @inheritDoc
     */
    public function collection(): Collection
    {
        $this->builder = $this->model->newQuery();

        $this->filterAndOrder();

        return $this->builder->get();
    }

    /**
     * @inheritDoc
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
    public function exists(string $value, ?string $key = null): bool
    {
        $this->builder = $this->model->newQuery();

        $this->filterAndOrder();

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

        $this->filterAndOrder();

        return $this->builder->first();
    }

    /**
     * @inheritDoc
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
     */
    public function delete(Model $model): void
    {
        $this->model = $model;
        $this->model->delete();
    }

    /**
     * @inheritDoc
     */
    public function setFilter(?Filter $filter): self
    {
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
     * @return void
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
        $joins = $this->builder->getQuery()->joins;

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
     * @return void
     */
    protected function reset(): void
    {
        $this->filter = null;
        $this->order = null;
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
}
