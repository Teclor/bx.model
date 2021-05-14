<?php


namespace Bx\Model;

use ArrayIterator;
use Bx\Model\Interfaces\CollectionInterface;
use Bx\Model\Interfaces\CollectionItemInterface;
use SplObjectStorage;

class ModelCollection implements CollectionInterface
{
    /**
     * @var AbsOptimizedModel[]|CollectionItemInterface[]|SplObjectStorage
     */
    protected $items;
    /**
     * @var string
     */
    protected $className;

    public function __construct($list, string $className)
    {
        $this->items = new SplObjectStorage();

        $this->className = $className;
        foreach ($list as $item) {
            if ($item instanceof $className) {
                $this->items->attach($item);
                continue;
            }

            $this->items->attach(new $className($item));
        }
    }

    /**
     * @param CollectionItemInterface $item
     * @return void
     */
    public function append(CollectionItemInterface $item)
    {
        if ($item instanceof $this->className) {
            $this->items->attach($item);
        }
    }

    /**
     * @param CollectionItemInterface $item
     * @return void
     */
    public function remove(CollectionItemInterface $item)
    {
        if ($this->items->contains($item)) {
            $this->items->detach($item);
        }
    }

    /**
     * @deprecated
     * @param AbsOptimizedModel $model
     * @return void
     */
    public function addModel(AbsOptimizedModel $model)
    {
        $this->append($model);
    }

    public function add(array $modelData)
    {
        $this->append(new $this->className($modelData));
    }

    /**
     * @return AbsOptimizedModel[]|SplObjectStorage|ArrayIterator
     */
    public function getIterator()
    {
        $this->items->rewind();
        return $this->items;
    }

    /**
     * @return AbsOptimizedModel|CollectionItemInterface|null
     */
    public function first(): ?CollectionItemInterface
    {
        $current = $this->getIterator()->current();
        return $current instanceof CollectionItemInterface ? $current : null;
    }

    /**
     * @return int
     */
    public function count()
    {
        return count($this->items);
    }

    /**
     * @param string $key
     * @param string|null $indexKey
     * @param callable|null $fnModifier - attribute is mixed value by the key of the collection item
     * @return array
     */
    public function column(string $key, string $indexKey = null, callable $fnModifier = null): array
    {
        $result = [];
        $isCallable = $fnModifier !== null;
        foreach($this as $item) {
            $itemKey = null;
            if (!empty($indexKey) && $item->hasValueKey($indexKey)) {
                $itemKey = $item->getValueByKey($indexKey);
            }

            $value = $item->hasValueKey($key) ? $item->getValueByKey($key) : null;
            if (empty($itemKey)) {
                $result[] = $isCallable ? $fnModifier($value) : $value;
            } else {
                $result[$itemKey] = $isCallable ? $fnModifier($value) : $value;
            }
        }

        return $result;
    }

    /**
     * @param string $key
     * @param callable $fn - attribute is mixed value by the key of the collection item
     * @return array
     */
    public function unique(string $key, callable $fnModifier = null): array
    {
        $result = [];
        $isCallable = $fnModifier !== null;
        foreach($this as $item) {
            $value = $item->hasValueKey($key) ? $item->getValueByKey($key) : null;
            $result[$value] = $isCallable ? $fnModifier($value) : $value;
        }

        return array_values($result);
    }

    /**
     * @param callable $fn
     * @return array
     */
    public function map(callable $fn): array
    {
        return array_map($fn, iterator_to_array($this->items));
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return AbsOptimizedModel[]|ModelCollection
     */
    public function filterByKey(string $key, $value): CollectionInterface
    {
        $newCollection = new static([], $this->className);
        foreach($this as $item) {
            if ($item->hasValueKey($key) && $item->assertValueByKey($key, $value)) {
                $newCollection->append($item);
            }
        }

        return $newCollection;
    }

    /**
     * @deprecated
     * @param string $fieldName
     * @param $value
     * @return $this
     */
    public function filerByColumn(string $fieldName, $value): self
    {
        return $this->filterByKey($fieldName, $value);
    }

    /**
     * @param callable $fn
     * @return $this
     */
    public function filter(callable $fn): self
    {
        return new static(array_filter(iterator_to_array($this->items), $fn), $this->className);
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return CollectionItemInterface|null
     */
    public function findByKey(string $key, $value): ?CollectionItemInterface
    {
        foreach($this as $item) {
            if ($item->hasValueKey($key) && $item->assertValueByKey($key, $value)) {
                return $item;
            }
        }

        return null;
    }

    /**
     * @param string $fieldName
     * @param $value
     * @return AbsOptimizedModel|null
     */
    public function findByColumn(string $fieldName, $value): ?CollectionItemInterface
    {
        return $this->find(function ($item) use ($fieldName, $value) {
            return isset($item[$fieldName]) && $item[$fieldName] == $value;
        });
    }

    /**
     * @param $fn
     * @return AbsOptimizedModel|null
     */
    public function find($fn): ?AbsOptimizedModel
    {
        foreach($this as $item) {
            if ($fn($item) === true) {
                return $item;
            }
        }

        return null;
    }

    /**
     * @param int $index
     * @return AbsOptimizedModel|null
     */
    public function getByIndex(int $index): ?AbsOptimizedModel
    {
        $list = iterator_to_array($this->items) ?? [];
        return $list[$index] ?? null;
    }

    /**
     * @return array
     */
    public function getApiModel(): array
    {
        $result = [];
        foreach($this as $item) {
            $result[] = $item->jsonSerialize();
        }

        return $result;
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->getApiModel();
    }
}
