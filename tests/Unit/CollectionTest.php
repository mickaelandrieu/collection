<?php

namespace Pitchart\Collection\Test\Unit;

use Pitchart\Collection\Collection;

class CollectionTest extends \PHPUnit_Framework_TestCase
{

    public function testCanBeInstantiated()
    {
        $collection = new Collection(array());
        self::assertInstanceOf(Collection::class, $collection);
        self::assertInstanceOf(\Countable::class, $collection);
        self::assertInstanceOf(\ArrayAccess::class, $collection);
    }

    public function testCanBeBuildedFromArrays()
    {
        $collection = Collection::from(array());
        self::assertInstanceOf(Collection::class, $collection);
        self::assertInstanceOf(\Countable::class, $collection);
        self::assertInstanceOf(\ArrayAccess::class, $collection);
    }

    public function testCanBeBuildedFromIterators()
    {
        $collection = Collection::from(new \ArrayIterator(array()));
        self::assertInstanceOf(Collection::class, $collection);
        self::assertInstanceOf(\Countable::class, $collection);
        self::assertInstanceOf(\ArrayAccess::class, $collection);
    }

    /**
     * @param mixed $argument
     * @param string $type
     * @dataProvider badArgumentProvider
     */
    public function testBuildFromBadArgumentThrowsAnException($argument, $type)
    {
        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage(sprintf('Argument 1 must be an instance of Traversable or an array, %s given', $type));
        $collection = Collection::from($argument);
    }

    /**
     * @var callable $callable
     * @dataProvider callableProvider
     */
    public function testCanExecuteCallables(callable $callable)
    {
        $reflection = new \ReflectionClass(Collection::class);
        $method = $reflection->getMethod('normalizeAsCallables');
        $method->setAccessible(true);
        /** @var \Closure $function */
        $function = $method->invokeArgs(new Collection, [$callable]);
        self::assertEquals('2017-01-01', $function('Y-m-d', '2017-01-01')->format('Y-m-d'));
    }

    /**
     * @param array $items
     * @param int   $numberOfItems
     * @dataProvider countTestProvider
     */
    public function testCanReturnNumberOfItems(array $items, $numberOfItems)
    {
        $collection = new Collection($items);
        self::assertEquals($numberOfItems, $collection->count());
    }

    public function testCanIterateItems()
    {
        $collection = Collection::from([1, 2, 3, 4]);
        $mock = self::getMockBuilder(\stdClass::class)
            ->setMethods(['test'])
            ->getMock();

        $mock->expects(self::exactly(4))
            ->method('test');
        $function = function ($item) use ($mock) {
            $mock->test($item);
        };

        $collection->each($function);
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
        $collection = new Collection($items);
        self::assertEquals($expected, $collection->reduce($reducer, $initial));
    }

    /**
     * @param array    $items
     * @param callable $callback
     * @param array    $expected
     * @dataProvider filterTestProvider
     */
    public function testCanBeFiltered(array $items, callable $callback, array $expected)
    {
        $collection = new Collection($items);
        self::assertEquals($expected, array_values($collection->filter($callback)->toArray()));
        // test the alias
        self::assertEquals($expected, array_values($collection->select($callback)->toArray()));
    }

    /**
     * @param array    $items
     * @param callable $callback
     * @param array    $expected
     * @dataProvider rejectTestProvider
     */
    public function testCanBeRejected(array $items, callable $callback, array $expected)
    {
        $collection = new Collection($items);
        self::assertEquals($expected, array_values($collection->reject($callback)->toArray()));
    }

    /**
     * @param array    $items
     * @param callable $callback
     * @param array    $expected
     * @dataProvider mapTestProvider
     */
    public function testCanMapDatas(array $items, callable $callback, array $expected)
    {
        self::assertEquals($expected, Collection::from($items)->map($callback)->toArray());
    }

    public function testCanGroupItems()
    {
        $testItem1 = (object) ['name' => 'bar', 'age' => 20];
        $testItem2 = (object) ['name' => 'fizz', 'age' => 30];
        $items = [
            (object) ['name' => 'foo', 'age' => 10],
            $testItem1,
            (object) ['name' => 'baz', 'age' => 25],
            $testItem2,
            (object) ['name' => 'buzz', 'age' => 40],
        ];
        $collection = Collection::from($items);
        $grouped = $collection->groupBy(
            function ($item) {
                return $item->age <= 25;
            }
        );

        // Grouped Collection only contains instances of Collection
        foreach ($grouped as $group) {
            self::assertInstanceOf(Collection::class, $group);
        }
        // Group by a boolean function returns 2 groups
        self::assertEquals(2, $grouped->count());
        // Test items distribution
        self::assertContains($testItem1, $grouped->offsetGet(1));
        self::assertContains($testItem2, $grouped->offsetGet(0));
    }

    public function testCanMergeCollections()
    {
        $collection = new Collection([1, 2, 3]);
        $merged = $collection->merge(Collection::from([4, 5, 6]));
        self::assertEquals([1, 2, 3, 4, 5, 6], $merged->values());
    }

    public function testCanCollapseCollectionOfCollections()
    {
        $items = [
            Collection::from([1, 2, 3]),
            Collection::from([4, 5, 6]),
        ];

        $expected = [1, 2, 3, 4, 5, 6];

        $collection = Collection::from($items)->concat();

        self::assertEquals($expected, $collection->values());
    }

    public function testCanRemoveDuplicates()
    {
        $items = [1, 6, 3, 4, 3, 5, 5, 3, 2, 1];
        $collection = Collection::from($items)->distinct();

        foreach ($collection->values() as $key => $value) {
            $datas = $collection->values();
            unset($datas[$key]);
            self::assertNotContains($value, $datas);
        }
    }

    public function testCanSortItems()
    {
        $sorted = Collection::from([3, 1, 2, 4])->sort(
            function ($first, $second) {
                return ($first == $second ? 0 : ($first < $second ? -1 : 1));
            }
        );
        self::assertEquals([1, 2, 3, 4], $sorted->values());
    }

    public function testCanExtractParts()
    {
        $sliced = Collection::from([1, 2, 3, 4])->slice(1, 2);
        self::assertEquals([2, 3], $sliced->values());
    }

    public function testCanExtratNthFirstItems()
    {
        $firsts = Collection::from([1, 2, 3, 4])->take(3);
        self::assertEquals([1, 2, 3], $firsts->values());
    }

    public function testCanRemoveItemsFromAnotherCollection()
    {
        $difference = Collection::from([1, 2, 3, 4])->difference(new Collection([2, 3]));
        self::assertEquals([1, 4], $difference->values());
    }

    public function testCanRetainItemsAlsoInAnotherCollection()
    {
        $intersection = Collection::from([1, 2, 3, 4])->intersection(new Collection([2, 3]));
        self::assertEquals([2, 3], $intersection->values());
    }

    public function testEveryReturnsTrueIfAllItemsSatisfyCondition()
    {
        $collection = Collection::from([2,4,6,8,10]);
        self::assertTrue($collection->every(function ($item) {
            return $item % 2 == 0;
        }));
    }

    public function testEveryReturnsFalseIfAtLeastOneItemDoesntSatisfyCondition()
    {
        $collection = Collection::from([2,3,6,8,10]);
        self::assertFalse($collection->every(function ($item) {
            return $item % 2 == 0;
        }));
    }

    public function testSomeReturnsTrueIfAllItemsSatisfyCondition()
    {
        $collection = Collection::from([2,4,6,8,10]);
        self::assertTrue($collection->some(function ($item) {
            return $item % 2 == 0;
        }));
    }

    public function testSomeReturnsTrueIfAtLeastOneItemSatisfiesCondition()
    {
        $collection = Collection::from([2,3,6,8,10]);
        self::assertTrue($collection->some(function ($item) {
            return $item % 2 == 0;
        }));
    }

    public function testSomeReturnsFalseIfAllItemsDontSatisfyCondition()
    {
        $collection = Collection::from([1,3,5,7,9]);
        self::assertFalse($collection->some(function ($item) {
            return $item % 2 == 0;
        }));
    }

    public function testNoneReturnsFalseIfAtLeastOneItemSatisfiesCondition()
    {
        $collection = Collection::from([1,3,6,7,9]);
        self::assertFalse($collection->none(function ($item) {
            return $item % 2 == 0;
        }));
    }

    public function testNoneReturnsTrueIfNoItemSatisfiesCondition()
    {
        $collection = Collection::from([1,3,5,7,9]);
        self::assertTrue($collection->none(function ($item) {
            return $item % 2 == 0;
        }));
    }

    public function testCanFlattenElementsAfterAMapping()
    {
        $flatMap = Collection::from([1, 2, 3, 4])->flatMap(
            function ($item) {
                return Collection::from([$item, $item + 1]);
            }
        );
        self::assertEquals([1, 2, 2, 3, 3, 4, 4, 5], $flatMap->values());
        $flatMap = Collection::from([1, 2, 3, 4])->mapcat(
            function ($item) {
                return Collection::from([$item, $item + 1]);
            }
        );
        self::assertEquals([1, 2, 2, 3, 3, 4, 4, 5], $flatMap->values());
    }

    public function testCanExtractTheFirstElement()
    {
        self::assertEquals(1, Collection::from([1,2,3])->head());
    }

    public function testCanExtractAllElementsButFirst()
    {
        self::assertEquals([2,3], Collection::from([1,2,3])->tail()->values());
    }

    /**
     * Test that transformation methods keeps the collection immutable
     *
     * @param        array    $items
     * @param        $func
     * @param        callable $callback
     * @dataProvider immutabilityTestProvider
     */
    public function testMethodsKeepImmutability(array $items, $func, array $params)
    {
        $collection = Collection::from($items);
        call_user_func_array(array($collection, $func), $params);
        self::assertEquals($items, $collection->toArray());
    }

    public function immutabilityTestProvider()
    {
        return [
            'each' => [[1, 2, 3, 4], 'each', [function ($item) {
                return $item + 1;
            }]],
            'map' => [[1, 2, 3, 4], 'map', [function ($item) {
                return $item + 1;
            }]],
            'filter' => [[1, 2, 3, 4], 'filter', [function ($item) {
                return $item % 2 == 0;
            }]],
            'select' => [[1, 2, 3, 4], 'select', [function ($item) {
                return $item % 2 == 0;
            }]],
            'reject' => [[1, 2, 3, 4], 'reject', [function ($item) {
                return $item % 2 == 0;
            }]],
            'reduce' => [[1, 2, 3, 4], 'reduce', [function ($accumulator, $item) {
                return $item + $accumulator;
            }, 0]],
            'sort' => [[1, 2, 3, 4], 'sort', [function ($first, $second) {
                return ($first == $second ? 0 : ($first < $second ? -1 : 1));
            }]],
            'slice' => [[1, 2, 3, 4], 'slice', [1, 2, false]],
            'slice preserving keys' => [[1, 2, 3, 4], 'slice', [1, 2, true]],
            'take' => [[1, 2, 3, 4], 'take', [3, false]],
            'take preserving keys' => [[1, 2, 3, 4], 'slice', [3, true]],
            'difference' => [[1, 2, 3, 4], 'difference', [new Collection([3, 4])]],
            'intersection' => [[1, 2, 3, 4], 'intersection', [new Collection([3, 4])]],
            'merge' => [[1, 2, 3, 4], 'merge', [new Collection([3, 4])]],
            'flatMap' => [[1, 2, 3, 4], 'flatMap', [function ($item) {
                return Collection::from([$item, $item + 1]);
            }]],
            'mapcat' => [[1, 2, 3, 4], 'mapcat', [function ($item) {
                return Collection::from([$item, $item + 1]);
            }]],
        ];
    }

    public function badArgumentProvider()
    {
        return [
            [null, 'NULL'],
            [true, 'boolean'],
            ['toto', 'string'],
            [15, 'integer'],
            [new \stdClass(), 'stdClass'],
        ];
    }

    public function callableProvider()
    {
        return [
            'Function name' => ['date_create_from_format'],
            'Closure' => [function ($format, $time) {
                return date_create_from_format($format, $time);
            }],
            'Static method string' => ['DateTime::createFromFormat'],
            'Array with class name and static method' => [['DateTime', 'createFromFormat']],
            'Array with object and method name' => [[new \DateTime, 'createFromFormat']],
        ];
    }

    public function countTestProvider()
    {
        return [
            'An empty array' => [[], 0],
            'An array with elements' => [[1,2,3], 3],
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

    public function rejectTestProvider()
    {
        return [
            'Pair filter' => [[1,2,3, 4], function ($item) {
                return $item % 2 == 0;
            }, [1, 3]],
            'String filter' => [['foo', 'bar', 'fizz', 'buzz'], function ($item) {
                return strpos($item, 'f') !== false;
            }, ['bar', 'buzz']],
        ];
    }
}
