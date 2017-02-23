<?php

namespace Pitchart\Collection;

class GeneratorCollection extends \IteratorIterator
{
    public static function from(\Iterator $iterator) {
        return new static($iterator);
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return iterator_to_array($this->getInnerIterator());
    }

    /**
     * @return Collection
     */
    public function persist()
    {
        return new Collection($this->toArray());
    }

    /**
     * @param callable $callable
     * @return static
     */
    public function map(callable $callable)
    {
        $mapping = function ($iterator) use ($callable) {
            foreach ($iterator as $key => $item) {
                yield $key => $callable($item);
            }
        };
        return new static($mapping($this->getInnerIterator()));
    }

    /**
     * @param callable $callable
     * @return static
     */
    public function filter(callable $callable)
    {
        $filtering = function ($iterator) use ($callable) {
            foreach ($iterator as $key => $item) {
                if ($callable($item)) {
                    yield $item;
                }
            }
        };
        return new static($filtering($this->getInnerIterator()));
    }

    /**
     * @param callable $callable
     * @return GeneratorCollection
     */
    public function select(callable $callable)
    {
        return $this->filter($callable);
    }

    /**
     * @param callable $callable
     * @param mixed    $initial
     * @return mixed
     */
    public function reduce(callable $callable, $initial)
    {
        $accumulator = $initial;
        //$function = $this->normalizeAsCallables($callable);

        foreach ($this->getInnerIterator() as $item) {
            $accumulator = $callable($accumulator, $item);
        }
        return $accumulator;
    }

}