<?php

namespace Pitchart\Collection\Test\Unit;

use Pitchart\Collection\Collection;
use Pitchart\Collection\GeneratorCollection;

class GeneratorCollectionTest extends \PHPUnit_Framework_TestCase
{
    public function testCanBeInstantiated()
    {
        $collection = new GeneratorCollection(new \ArrayIterator(array()));
        $this->assertInstanceOf(GeneratorCollection::class, $collection);
    }

    /**
     * @param array    $items
     * @param callable $callback
     * @param array    $expected
     * @dataProvider mapTestProvider
     */
    public function testCanMapDatas(array $items, callable $callback, array $expected)
    {
        $this->assertEquals($expected, GeneratorCollection::from(new \ArrayIterator($items))->map($callback)->toArray());
    }

    /**
     * @param array    $items
     * @param callable $callback
     * @param array    $expected
     * @dataProvider filterTestProvider
     */
    public function testCanBeFiltered(array $items, callable $callback, array $expected)
    {
        $collection = new GeneratorCollection(new \ArrayIterator($items));
        $this->assertEquals($expected, $collection->filter($callback)->toArray());
        // test the alias
        $this->assertEquals($expected, $collection->select($callback)->toArray());
    }

    public function testCanGetValuesAfterMappingOrFiltering() {
        $collection = new GeneratorCollection(new \ArrayIterator([0,1,2,3,4,5,6]));
        $values = $collection->map(function($item) { return $item; })->toArray();
        $this->assertEquals([0,1,2,3,4,5,6], $values);

        $collection = new GeneratorCollection(new \ArrayIterator([0,1,2,3,4,5,6]));
        $values = $collection->filter(function($item) { return $item % 2 == 0; })->toArray();
        $this->assertEquals([0,2,4,6], $values);
    }

    public function testCanTransformIntoCollection() {
        $collection = new GeneratorCollection(new \ArrayIterator([0,1,2,3,4,5,6]));
        $persisted = $collection->persist();
        $this->assertInstanceOf(Collection::class, $persisted);
        $this->assertEquals([0,1,2,3,4,5,6], $persisted->values());
    }

    /**
     * @param array    $items
     * @param callable $reducer
     * @param mixed    $initial
     * @param mixed    $expected
     * @dataProvider reduceTestProvider
     */
    public function testCanBeReduced(array $items, callable $reducer, $initial, $expected)
    {
        $collection = new GeneratorCollection(new \ArrayIterator($items));;
        $this->assertEquals($expected, $collection->reduce($reducer, $initial));
    }

    public function mapTestProvider()
    {
        return [
            'Add 1 mapper' => [[1,2,3], function ($item) {
                return $item + 1;
            }, [2, 3, 4]],
            'Concat mapper' => [['test1', 'test2', 'test3'], function ($item) {
                return $item.'1';
            }, ['test11', 'test21', 'test31']],
            'Empty data mapper' => [[], function ($item) {
                return $item + 1;
            }, []],
        ];
    }

    public function filterTestProvider()
    {
        return [
            'Pair filter' => [[1,2,3, 4], function ($item) {
                return $item % 2 == 0;
            }, [2, 4]],
            'String filter' => [['foo', 'bar', 'fizz', 'buzz'], function ($item) {
                return strpos($item, 'f') !== false;
            }, ['foo', 'fizz']],
        ];
    }

    public function reduceTestProvider()
    {
        return [
            'Sum reducing' => [[1,2,3,4], function ($accumulator, $item) {
                return $accumulator + $item;
            }, 0, 10],
            'String reducing' => [['banana', 'apple', 'orange'], function ($accumulator, $item) {
                return trim($accumulator.', '.$item, ', ');
            }, '', 'banana, apple, orange'],
        ];
    }

}