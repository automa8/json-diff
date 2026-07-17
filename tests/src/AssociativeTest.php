<?php

namespace Swaggest\JsonDiff\Tests;


use Swaggest\JsonDiff\JsonDiff;
use Swaggest\JsonDiff\JsonPatch;

class AssociativeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @throws \Swaggest\JsonDiff\Exception
     */
    public function testDiffAssociative()
    {
        $originalJson = <<<'JSON'
{
    "key1": [4, 1, 2, 3],
    "key2": 2,
    "key3": {
        "sub0": 0,
        "sub1": "a",
        "sub2": "b"
    },
    "key4": [
        {"a":1, "b":true, "subs": [{"s":1}, {"s":2}, {"s":3}]}, {"a":2, "b":false}, {"a":3}
    ]
}
JSON;

        $newJson = <<<'JSON'
{
    "key5": "wat",
    "key1": [5, 1, 2, 3],
    "key4": [
        {"c":false, "a":2}, {"a":1, "b":true, "subs": [{"s":3, "add": true}, {"s":2}, {"s":1}]}, {"c":1, "a":3}
    ],
    "key3": {
        "sub3": 0,
        "sub2": false,
        "sub1": "c"
    }
}
JSON;

        $diff = new JsonDiff(json_decode($originalJson), json_decode($newJson));
        $expected = json_encode($diff->getPatch()->jsonSerialize(), JSON_PRETTY_PRINT + JSON_UNESCAPED_SLASHES);

        $diff = new JsonDiff(json_decode($originalJson, true), json_decode($newJson, true),
            JsonDiff::TOLERATE_ASSOCIATIVE_ARRAYS);
        $actual = json_encode($diff->getPatch()->jsonSerialize(), JSON_PRETTY_PRINT + JSON_UNESCAPED_SLASHES);

        $this->assertEquals($expected, $actual);

        $original = json_decode($originalJson, true);
        $newJson = json_decode($newJson, true);
        $patch = JsonPatch::import(json_decode($actual, true));
        $patch->setFlags(JsonPatch::TOLERATE_ASSOCIATIVE_ARRAYS);
        $patch->apply($original);
        $this->assertEquals($newJson, $original);
    }

    /**
     * Avoid PathException with objects like {"+1": ...}
     *
     * @throws \Swaggest\JsonDiff\Exception
     */
    public function testSignedIntegerLikeKey()
    {
        $original = [
            '+1' => [
                ['name' => 'first', 'keep' => 1],
                ['name' => 'second', 'keep' => 2],
            ],
            '-1' => [
                ['name' => 'other', 'keep' => 3],
            ],
        ];

        $patch = JsonPatch::import([
            ['op' => 'replace', 'path' => '/+1/1/name', 'value' => 'patched'],
            ['op' => 'replace', 'path' => '/-1/0/name', 'value' => 'negative'],
        ]);
        $patch->setFlags(JsonPatch::TOLERATE_ASSOCIATIVE_ARRAYS);
        $patch->apply($original);

        $this->assertSame('patched', $original['+1'][1]['name']);
        $this->assertSame('negative', $original['-1'][0]['name']);
        $this->assertSame('first', $original['+1'][0]['name']);
        $this->assertArrayNotHasKey(1, $original);
    }
}
