<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Translation\Console;

use Doctrine\DBAL\Types\Type;
use Kdyby;
use Kdyby\Console\ContainerHelper;
use Nette;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;



/**
 * @author Azathoth <memnarch@seznam.cz>
 *
 * @method ContainerHelper|Helper getHelper(string $name)
 */
class CreateTableCommand extends Command
{

	/**
	 * @var Kdyby\Translation\Loader\IDatabaseLoader
	 */
	private $databaseLoader;



	protected function configure()
	{
		$this->setName('kdyby:translation:create-table')
			->setDescription('Builds query for creating of database table.')
			->addOption('dump-sql', NULL, InputOption::VALUE_NONE, 'Dumps the generated SQL statement to the screen (does not execute it).')
			->addOption('force', NULL, InputOption::VALUE_NONE, 'Causes the generated SQL statement to be physically executed against your database.');
	}



	protected function initialize(InputInterface $input, OutputInterface $output)
	{
		$serviceLocator = $this->getHelper('container')->getContainer();
		$this->databaseLoader = $serviceLocator->getByType('Kdyby\Translation\Loader\IDatabaseLoader');
	}



	protected function execute(InputInterface $input, OutputInterface $output)
	{
		if (!$input->getOption('dump-sql') && !$input->getOption('force')) {
			$output->writeln('<error>You must run the command either with --dump-sql or --force.</error>');
			return 1;
		}

		try {
			$queries = $this->databaseLoader->setupDatabase($input->getOption('force'));

			if ($input->getOption('force')) {
				$output->writeln(sprintf('Database schema updated successfully! Translation table created.'));
			}

			if ($input->getOption('dump-sql')) {
				$output->writeln(implode(";\n", $queries));
			}

			return 0;

		} catch (Kdyby\Translation\DatabaseException $e) {
			$this->getApplication()->renderException($e, $output);
			return 1;
		}
	}

}
