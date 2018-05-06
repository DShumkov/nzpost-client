<?php

namespace DShumkov\NzPostClient;

use Psr\SimpleCache\CacheInterface;

interface NzPostClientInterface
{
    /**
     * NzPostClientInterface constructor.
     * @param string $clientID
     * @param string $secret
     * @param CacheInterface|NULL $CacheItemPool
     */
    public function __construct($clientID, $secret, CacheInterface $CacheItemPool = NULL);

    /**
     * @param array $addressLines
     * @param string $type
     * @param int $max
     * @return array
     */
    public function find(array $addressLines, $type = 'All', $max = 10);

    /**
     * @param $dpid
     * @param string $type
     * @param int $max
     * @return array
     */
    public function details($dpid, $type = 'All', $max = 10);

    /**
     * @param $query
     * @param string $type
     * @param int $max
     * @return array
     */
    public function suggest($query, $type = 'All', $max = 10);

    /**
     * @param $query
     * @param string $order_roads_first
     * @param int $max
     * @return array
     */
    public function suggestPartial($query, $order_roads_first = 'N', $max = 10);

    /**
     * @param $unique_id
     * @param int $max
     * @return array
     */
    public function partialDetails($unique_id, $max = 10);

}