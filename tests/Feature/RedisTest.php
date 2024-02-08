<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Support\Facades\Redis;
use Predis\Command\Argument\Geospatial\ByRadius;
use Predis\Command\Argument\Geospatial\FromLonLat;

class RedisTest extends TestCase
{
    public function testPing()
    {
        $response = Redis::command("ping");
        self::assertEquals("PONG", $response);

        $response = Redis::ping();
        self::assertEquals("PONG", $response);
    }

    public function testString()
    {
        Redis::setex("name", 2, "Marcell");
        $response = Redis::get("name");
        self::assertEquals("Marcell", $response);

        sleep(5);

        $response = Redis::get("name");
        self::assertNull($response);
    }

    public function testList()
    {
        Redis::del("names");

        Redis::rpush("names", "Marcell");
        Redis::rpush("names", "Budi");
        Redis::rpush("names", "Putra");

        $response = Redis::lrange("names", 0, -1);
        self::assertEquals(["Marcell", "Budi", "Putra"], $response);

        self::assertEquals("Marcell", Redis::lpop("names"));
        self::assertEquals("Budi", Redis::lpop("names"));
        self::assertEquals("Putra", Redis::lpop("names"));

    }

    public function testSet()
{
    Redis::del("names");

    Redis::sadd("names", "Marcell");
    Redis::sadd("names", "Marcell");
    Redis::sadd("names", "Budi");
    Redis::sadd("names", "Budi");
    Redis::sadd("names", "Putra");
    Redis::sadd("names", "Putra");

    $response = Redis::smembers("names");

    // Remove duplicates from the expected array
    $expected = ["Marcell", "Budi", "Putra"];

    // Sort both arrays to compare them irrespective of order
    sort($expected);
    sort($response);

    self::assertEquals($expected, $response);
}


    public function testSortedSet()
    {

        Redis::del("names");

        Redis::zadd("names", 100, "Marcell");
        Redis::zadd("names", 100, "Marcell");
        Redis::zadd("names", 85, "Budi");
        Redis::zadd("names", 85, "Budi");
        Redis::zadd("names", 95, "Putra");
        Redis::zadd("names", 95, "Putra");

        $response = Redis::zrange("names", 0, -1);
        self::assertEquals(["Budi", "Putra", "Marcell"], $response);
    }

    public function testHash()
    {
        Redis::del("user:1");

        Redis::hset("user:1", "name", "Marcell");
        Redis::hset("user:1", "email", "marcell@gmail.com");
        Redis::hset("user:1", "age", 30);

        $response = Redis::hgetall("user:1");
        self::assertEquals([
            "name" => "Marcell",
            "email" => "marcell@gmail.com",
            "age" => "30"
        ], $response);
    }

    public function testGeoPoint()
    {
        Redis::del("sellers");

        Redis::geoadd("sellers", 106.820990, -6.174704, "Toko A");
        Redis::geoadd("sellers", 106.822696, -6.176870, "Toko B");

        $result = Redis::geodist("sellers", "Toko A", "Toko B", "km");
        self::assertEquals(0.3061, $result);

        $result = Redis::geosearch("sellers", new FromLonLat(106.821666, -6.175494), new ByRadius(5, "km"));
        self::assertEquals(["Toko A", "Toko B"], $result);
    }

    public function testHyperLogLog()
    {
        Redis::pfadd("visitors", "marcell", "budi", "putra");
        Redis::pfadd("visitors", "marcell", "suyud", "natura");
        Redis::pfadd("visitors", "ari", "suyud", "natura");

        $result = Redis::pfcount("visitors");
        self::assertEquals(12, $result);

    }

    public function testPipeline()
    {
        Redis::pipeline(function ($pipeline){
            $pipeline->setex("name", 2, "Marcell");
            $pipeline->setex("address", 2, "Indonesia");
        });

        $response = Redis::get("name");
        self::assertEquals("Marcell", $response);
        $response = Redis::get("address");
        self::assertEquals("Indonesia", $response);
    }

    public function testTransaction()
    {
        Redis::transaction(function ($transaction){
            $transaction->setex("name", 2, "Marcell");
            $transaction->setex("address", 2, "Indonesia");
        });

        $response = Redis::get("name");
        self::assertEquals("Marcell", $response);
        $response = Redis::get("address");
        self::assertEquals("Indonesia", $response);
    }

    public function testPublish()
    {
        for ($i = 0; $i < 10; $i++) {
            Redis::publish("channel-1", "Hello Marcell $i");
            Redis::publish("channel-2", "Good Bye $i");
        }
        self::assertTrue(true);
    }

    public function testPublishStream()
    {
        for ($i = 0; $i < 10; $i++) {
            Redis::xadd("members", "*", [
                "name" => "Marcell $i",
                "address" => "Indonesia"
            ]);
        }
        self::assertTrue(true);
    }

    public function testCreateConsumer()
    {
        Redis::xgroup("create", "members", "group1", "0");
        Redis::xgroup("createconsumer", "members", "group1", "consumer-1");
        Redis::xgroup("createconsumer", "members", "group1", "consumer-2");
        self::assertTrue(true);

    }

    public function testConsumerStream()
    {
        $result = Redis::xreadgroup("group1", "consumer-1", ["members" => ">"], 3, 3000);

        self::assertNotNull($result);
        echo json_encode($result, JSON_PRETTY_PRINT);
    }
}
