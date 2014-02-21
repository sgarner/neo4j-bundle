<?php
namespace id009\Neo4jbundle;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use HireVoice\Neo4j\EntityManager as BaseEntityManager;
use HireVoice\Neo4j\Configuration;
use HireVoice\Neo4j\Exception;
use Everyman\Neo4j\Cache;

/**
 * Entity Manager
 *
 * @author Alex Belyaev <lex@alexbelyaev.com>
 */
class EntityManager extends BaseEntityManager
{
	public function addSubscriber(EventSubscriberInterface $subscriber)
	{
		$events = $subscriber::getSubscribedEvents();
		foreach ($events as $event => $methods){
			if (!in_array($event, array($this::ENTITY_CREATE, $this::RELATION_CREATE, $this::QUERY_RUN))){
				throw new Exception(sprintf('Wrong event name "%s"', $event));
			}

			foreach ($methods as $method){
				$this->registerEvent($event, array($subscriber, $method));
			}
		}
	}

    public function setCache($driver, $service, $cacheTimeout = null)
    {
        switch ($driver) {
            case 'variable':
                $cache = new \Everyman\Neo4j\Cache\Variable();
                break;
            case 'memcache':
                $cache = new \Everyman\Neo4j\Cache\Memcache($service);
                break;
            case 'memcached':
                $cache = new \Everyman\Neo4j\Cache\Memcached($service);
                break;
            case 'null':
            default:
                $cache = new \Everyman\Neo4j\Cache\Null();
                break;
        }

        $this->getClient()->getEntityCache()->setCache($cache, $cacheTimeout);
    }
}