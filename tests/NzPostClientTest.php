<?php

use DShumkov\NzPostClient\NzPostClient;

class NzPostClientTest extends PHPUnit_Framework_TestCase
{
    public static $nzPostClient;

    public function setUp()
    {
        $clientID = getenv('NZPOST_CLIENT_ID');
        $secret = getenv('NZPOST_SECRET');
        $this->assertNotFalse($clientID);
        $this->assertNotFalse($secret);
        self::$nzPostClient = new NzPostClient($clientID, $secret);
    }

    public function testWeCanAuthWithCredentials()
    {
        $this->assertInstanceOf(NzPostClient::class, self::$nzPostClient);
    }

    public function testCanGetSuggestion()
    {
        $response = self::$nzPostClient->suggest('1 Queen', 'Postal', 5);
        $this->assertCount(5, $response);
        $this->assertArrayHasKey('DPID', $response[0]);
    }

    public function testFind()
    {
        $max = 3;
        $response = self::$nzPostClient->find(['1 Queen'], 'Postal', $max);
        $this->assertInternalType('array', $response);
        $this->assertCount($max, $response);

        return $response;
    }

    /**
     * @depends testFind
     */
    public function testDetails(array $response)
    {
        $dpid = $response[0]['DPID'];
        $max = 4;
        $response = self::$nzPostClient->details($dpid, 'All', $max);
        $this->assertInternalType('array', $response);
    }

    public function testSuggestPartial()
    {
        $query = 'queen';
        $response = self::$nzPostClient->suggestPartial($query);
        $this->assertInternalType('array', $response);
    }

    public function testPartialDetails()
    {
        $uniqId = 82868;
        $response = self::$nzPostClient->partialDetails($uniqId);
    }

}
