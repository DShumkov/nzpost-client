<?php

namespace DShumkov\NzPostClient;

use Psr\SimpleCache\CacheInterface;

/**
 * Class NzPostClient
 * @package DShumkov\NzPostClient
 */
class NzPostClient implements NzPostClientInterface
{
    /** Cache key for auth token storage  */
    const TOKEN = 'NZ_POST_AUTH_TOKEN';

    /** URL to NZ Post Auth API */
    const NZPOST_AUTH_URL = 'https://oauth.nzpost.co.nz/as/token.oauth2';

    /** URL to NZ Post Address Checker API */
    const NZPOST_API_URL = 'https://api.nzpost.co.nz/addresschecker/1.0/';

    /** Main app request timeout */
    const REQUEST_TIMEOUT = 120;

    /**
     * Turn on/off debugging mode
     * @var bool
     */
    protected $debug = FALSE;
    /**
     * Your NZ Post Client ID
     * @var string
     */
    protected $clientID;
    /**
     * Your NZ Post Client secret
     * @var string
     */
    protected $secret;
    /**
     * Auth token which we get from NZ Post Auth API
     * @var mixed
     */
    protected $token;
    /**
     * PSR-16 Cache (Optional)
     * @var NULL|CacheInterface
     */
    protected $Cache;
    /**
     * Cache TTL in seconds using only for NZ Post addresses not auth token
     * @var
     */
    protected $ttl = 86400;
    /**
     * Cache key prefix
     * @var string
     */
    protected $cachePrefix = 'nz_post_client_';

    /**
     * NzPostClient constructor. You can use yor PSR-16 cache to save money on same requests.
     * @param string $clientID
     * @param string $secret
     * @param CacheInterface|NULL $Cache
     */
    public function __construct($clientID, $secret, CacheInterface $Cache = NULL)
    {
        $this->clientID = $clientID;
        $this->secret = $secret;
        if (NULL !== $Cache) {
            $this->Cache = $Cache;

            if ($this->Cache->has(self::TOKEN)) {
                $this->token = $this->Cache->get(self::TOKEN);

                return;
            }
        }
        $this->auth();
    }

    /**
     * Authorize us in NZ Post Auth API to get auth token
     * @throws NzPostClientAuthException
     */
    protected function auth()
    {
        $params = [
            'grant_type' => 'client_credentials',
            'client_id' => $this->clientID,
            'client_secret' => $this->secret,
        ];

        $curlSession = curl_init(self::NZPOST_AUTH_URL);

        curl_setopt($curlSession, CURLOPT_POST, 1);
        curl_setopt($curlSession, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($curlSession, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($curlSession);

        $responseCode = curl_getinfo($curlSession, CURLINFO_HTTP_CODE);

        if (200 !== $responseCode) {
            throw new NzPostClientAuthException($response);
        }

        $body = json_decode($response, TRUE);
        if (!isset($body['access_token'])) {
            throw new NzPostClientAuthException('Could not get auth token from NZPOST API');
        }

        $this->token = $body['access_token'];

        if (isset($body['expires_in']) && $this->cacheIsSet()) {
            $expiresAt = $body['expires_in'] - self::REQUEST_TIMEOUT;

            $this->Cache->set(self::TOKEN, $body['access_token'], $expiresAt);
        }

    }

    /**
     * Check if there is cache set
     * @return bool
     */
    public function cacheIsSet()
    {
        return is_a($this->Cache, CacheInterface::class);
    }

    /**
     * Searching for address by address details
     * @param array $addressLines Address Line 1 ... Line 5
     * @param string $type Type of addresses to search: 'Postal|Physical|All'
     * @param int $max Maximum number of results to return.
     * @return array
     * @throws NzPostClientAPIException
     */
    public function find(array $addressLines, $type = 'All', $max = 10)
    {
        if ($this->cacheIsSet()) {
            $cacheKey = $this->cachePrefix . md5(implode($addressLines) . $type . strval($max));

            if ($this->Cache->has($cacheKey)) {
                return $this->Cache->get($cacheKey);
            }
        }

        $params = http_build_query([
            'type' => $type,
            'address_line_1' => (isset($addressLines[0]) ? $addressLines[0] : NULL),
            'address_line_2' => (isset($addressLines[1]) ? $addressLines[1] : NULL),
            'address_line_3' => (isset($addressLines[2]) ? $addressLines[2] : NULL),
            'address_line_4' => (isset($addressLines[3]) ? $addressLines[3] : NULL),
            'address_line_5' => (isset($addressLines[4]) ? $addressLines[4] : NULL),
            'max' => $max,
            'access_token' => $this->token,
        ]);

        $request = self::NZPOST_API_URL . 'find?' . $params;

        $responseBody = $this->sendApiRequest($request);

        if ($this->cacheIsSet()) {
            $this->Cache->set($cacheKey, $responseBody['addresses'], $this->ttl);
        }

        return $responseBody['addresses'];
    }

    /**
     * @param string $request
     * @return array
     * @throws NzPostClientAPIException
     */
    protected function sendApiRequest($request)
    {
        $curlSession = curl_init($request);

        curl_setopt($curlSession, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt($curlSession, CURLOPT_HTTPHEADER, ['Accept: application/json']);
        if ($this->debug) {
            curl_setopt($curlSession, CURLOPT_VERBOSE, TRUE);
        }
        $response = curl_exec($curlSession);
        $responseCode = curl_getinfo($curlSession, CURLINFO_HTTP_CODE);

        if (200 !== $responseCode) {
            throw new NzPostClientAPIException($responseCode);
        }

        return json_decode($response, TRUE);
    }

    /**
     * @param $dpid
     * @param string $type Type of addresses to search: 'Postal|Physical|All'
     * @param int $max
     * @return mixed
     * @throws NzPostClientAPIException
     */
    public function details($dpid, $type = 'All', $max = 10)
    {
        if ($this->cacheIsSet()) {
            $cacheKey = $this->cachePrefix . md5($dpid . $type . strval($max));

            if ($this->Cache->has($cacheKey)) {
                return $this->Cache->get($cacheKey);
            }
        }

        $params = http_build_query([
            'dpid' => $dpid,
            'type' => $type,
            'max' => $max,
            'access_token' => $this->token,
        ]);

        $request = self::NZPOST_API_URL . 'details?' . $params;

        $responseBody = $this->sendApiRequest($request);

        if ($this->cacheIsSet()) {
            $this->Cache->set($cacheKey, $responseBody['details'], $this->ttl);
        }

        return $responseBody['details'];
    }

    /**
     * @param $query
     * @param string $type Type of addresses to search: 'Postal|Physical|All'
     * @param int $max
     * @return array
     * @throws NzPostClientAPIException
     */
    public function suggest($query, $type = 'All', $max = 10)
    {
        if ($this->cacheIsSet()) {
            $cacheKey = $this->cachePrefix . md5($query . $type . strval($max));

            if ($this->Cache->has($cacheKey)) {
                return $this->Cache->get($cacheKey);
            }
        }

        $params = http_build_query([
            'q' => $query,
            'type' => $type,
            'max' => $max,
            'access_token' => $this->token,
        ]);

        $request = self::NZPOST_API_URL . 'suggest?' . $params;

        $responseBody = $this->sendApiRequest($request);

        if ($this->cacheIsSet()) {
            $this->Cache->set($cacheKey, $responseBody['addresses'], $this->ttl);
        }

        return $responseBody['addresses'];
    }

    /**
     * @param $query
     * @param string $orderRoadsFirst
     * @param int $max
     * @return array
     * @throws NzPostClientAPIException
     */
    public function suggestPartial($query, $orderRoadsFirst = 'N', $max = 10)
    {
        if ($this->cacheIsSet()) {
            $cacheKey = $this->cachePrefix . md5($query . $orderRoadsFirst . strval($max));

            if ($this->Cache->has($cacheKey)) {
                return $this->Cache->get($cacheKey);
            }
        }

        $params = http_build_query([
            'q' => $query,
            'order_roads_first' => $orderRoadsFirst,
            'max' => $max,
            'access_token' => $this->token,
        ]);

        $request = self::NZPOST_API_URL . 'suggest_partial?' . $params;

        $responseBody = $this->sendApiRequest($request);

        if ($this->cacheIsSet()) {
            $this->Cache->set($cacheKey, $responseBody['addresses'], $this->ttl);
        }

        return $responseBody['addresses'];
    }

    /**
     * @param $uniqueId
     * @param int $max
     * @return array
     * @throws NzPostClientAPIException
     */
    public function partialDetails($uniqueId, $max = 10)
    {
        if ($this->cacheIsSet()) {
            $cacheKey = $this->cachePrefix . md5($uniqueId . strval($max));

            if ($this->Cache->has($cacheKey)) {
                return $this->Cache->get($cacheKey);
            }
        }

        $params = http_build_query([
            'unique_id' => $uniqueId,
            'max' => $max,
            'access_token' => $this->token,
        ]);

        $request = self::NZPOST_API_URL . 'partial_details?' . $params;

        $responseBody = $this->sendApiRequest($request);

        if ($this->cacheIsSet()) {
            $this->Cache->set($cacheKey, $responseBody['details'], $this->ttl);
        }

        return $responseBody['details'];
    }

    /**
     * Get PSR-16 cache instance
     * @return NULL|CacheInterface
     */
    public function getCache()
    {
        return $this->Cache;
    }

    /**
     * Set PSR-16 cache instance
     * @param CacheInterface $cache
     * @param null|int|\DateTime $ttl
     * @return $this
     */
    public function setCache(CacheInterface $cache, $ttl = NULL)
    {
        $this->Cache = $cache;

        $this->ttl = $ttl;

        return $this;
    }

    /**
     * @return $this
     */
    public function disableCache()
    {
        $this->Cache = NULL;

        return $this;
    }

    /**
     * @return $this
     */
    public function setDebugOn()
    {
        $this->debug = TRUE;

        return $this;
    }

    /**
     * @return $this
     */
    public function setDebugOff()
    {
        $this->debug = FALSE;

        return $this;
    }

    /**
     * @return bool
     */
    public function isDebugOn()
    {
        return $this->debug === TRUE;
    }

    /**
     * @return string
     */
    public function getCachePrefix()
    {
        return $this->cachePrefix;
    }

    /**
     * @param string $cachePrefix
     * @return $this
     */
    public function setCachePrefix($cachePrefix)
    {
        $this->cachePrefix = $cachePrefix;

        return $this;
    }

    /**
     * @param int|\DateTime $ttl
     * @return $this
     */
    public function setTTL($ttl)
    {
        $this->ttl = $ttl;

        return $this;
    }

}