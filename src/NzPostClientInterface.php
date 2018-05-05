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

    public function find(array $addressLines, $type = 'All', $max = 10);

    public function details($dpid, $type = 'All', $max = 10);

    public function suggest($query, $type = 'All', $max = 10);

    public function suggestPartial($query, $order_roads_first = 'N', $max = 10);

    public function partialDetails($unique_id, $max = 10);

}