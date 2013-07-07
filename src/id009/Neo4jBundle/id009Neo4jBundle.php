<?php

namespace id009\Neo4jBundle;

use id009\Neo4jBundle\DependencyInjection\Compiler\CreateProxyDirectoryPass;
use id009\Neo4jBundle\DependencyInjection\Compiler\EventManagerPass;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Bridge\Doctrine\DependencyInjection\Security\UserProvider\EntityFactory;

/**
 * Neo4j Symfony bundle
 *
 * @author Alex Belyaev <lex@alexbelyaev.com>
 */
class id009Neo4jBundle extends Bundle
{
	public function build(ContainerBuilder $container)
	{
		parent::build($container);

		$container->addCompilerPass(new EventManagerPass());
		$container->addCompilerPass(new CreateProxyDirectoryPass(), PassConfig::TYPE_BEFORE_REMOVING);

		if ($container->hasExtension('security')) {
        	$container->getExtension('security')->addUserProviderFactory(new EntityFactory('neo4j', 'id009_neo4j.security.provider'));
        }
	}

    /**
     * {@inheritDoc}
     */
    public function boot()
    {
        // Register an autoloader for proxies to avoid issues when unserializing them
        // when the OGM is used. (Based on code from DoctrineBundle)

        $namespace = 'neo4jProxy';
        $dir = $this->container->getParameter('id009_neo4j.proxy_dir');
        // See https://github.com/symfony/symfony/pull/3419 for usage of
        // references
        $container =& $this->container;

        $this->autoloader = function($class) use ($dir, $namespace, &$container) {
            if (0 === strpos($class, $namespace)) {
                $fileName = str_replace('\\', '_', $class);
                $file = $dir.DIRECTORY_SEPARATOR.$fileName.'.php';

                if (!is_file($file)) {
                    $realClass = str_replace('_', '\\', substr($class, strlen($namespace)));
                    $em = $container->get('id009_neo4j.entity_manager');
                    $em->createProxy($realClass);
                }
                else {
                    require $file;
                }
            }
        };
        spl_autoload_register($this->autoloader);
    }
}
