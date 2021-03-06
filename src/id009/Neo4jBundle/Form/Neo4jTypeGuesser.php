<?php
namespace id009\Neo4jBundle\Form;

use Symfony\Component\Form\FormTypeGuesserInterface;
use Symfony\Component\Form\Guess\TypeGuess;
use Symfony\Component\Form\Guess\ValueGuess;
use Symfony\Component\Form\Guess\Guess;
use id009\Neo4jBundle\ManagerRegistry;

/**
 * Tries to guess a form type according mapping information.
 * It relies on ManyToOne or OneToOne relations
 *
 * @author Alex Belyaev <lex@alexbelyaev.com>
 */
class Neo4jTypeGuesser implements FormTypeGuesserInterface
{
	/**
	 * @var EntityManager
	 */
	private $entityManager;

	public function __construct(ManagerRegistry $managerRegistry)
	{
		$this->entityManager = $managerRegistry->getManager();
	}

	public function guessType($class, $property)
	{
		try{
			$meta = $this->entityManager->getRepository($class)->getMeta();
		} catch (\Exception $e){
			return new TypeGuess('text', array(), Guess::LOW_CONFIDENCE);
		}

		$choice = false;

		$manyToOneRelations = array();
		$manyToManyRelations = array();

		$manyToOneRelations = $meta->getManyToOneRelations();
		$manyToManyRelations = $meta->getManyToManyRelations();

		foreach ($manyToManyRelations as $p){
			if ($p->getName() == $property){
				$multiple = true;
				$choice = true;
				break;
			}
		}

		foreach ($manyToOneRelations as $p){
			if ($p->getName() == $property){
				$multiple = false;
				$choice = true;
				break;
			}
		}
		
		if ($choice)
		{
			return new TypeGuess(
				'neo4j_entity',
				array(
					'multiple' => $multiple,
					'expanded' => $multiple,
				),
				Guess::HIGH_CONFIDENCE
			);
		}
		
		return new TypeGuess('text', array(), Guess::MEDIUM_CONFIDENCE);
	}

	public function guessRequired($class, $property)
	{
		try{
			$meta = $this->entityManager->getRepository($class)->getMeta();
			$properties = $meta->getProperties();
			foreach ($properties as $prop){
				if ($prop->getName() == $property){
					return new ValueGuess(true, Guess::LOW_CONFIDENCE);
				}
			}

			return new ValueGuess(false, Guess::LOW_CONFIDENCE);
		} catch (\Exception $e){
			return new ValueGuess(false, Guess::LOW_CONFIDENCE);
		}
	}

	public function guessMaxLength($class, $property)
	{

	}

	public function guessMinLength($class, $property)
	{

	}

	public function guessPattern($class, $property)
	{

	}
}