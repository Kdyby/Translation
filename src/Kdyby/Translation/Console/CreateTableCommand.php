<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Translation\Console;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Types\Type;
use Kdyby;
use Nette;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class CreateTableCommand extends Command
{

	/** @var Nette\DI\Container */
	private $serviceLocator;

	/** @var Connection */
	private $connection;

	/** @var AbstractSchemaManager */
	private $schemaManager;

	/** @var string */
	public $table;

	/** @var string */
	public $key;

	/** @var string */
	public $locale;

	/** @var string */
	public $message;

	/** @var string */
	public $updatedAt;

	protected function configure()
	{
		$this->setName('kdyby:translation-create-table')
			->setDescription('Builds query for creating of database table.')
			->setDefinition(array(
				new InputOption(
					'dump-sql', NULL, InputOption::VALUE_NONE,
					'Dumps the generated SQL statement to the screen (does not execute it).'
				),
				new InputOption(
					'force', NULL, InputOption::VALUE_NONE,
					'Causes the generated SQL statement to be physically executed against your database.'
				)
			));
	}


	protected function initialize(InputInterface $input, OutputInterface $output)
	{
		$this->serviceLocator = $this->getHelper('container')->getContainer();
		$this->connection = $this->serviceLocator->getByType('Doctrine\DBAL\Connection');
		$this->schemaManager = $this->serviceLocator->getByType('Doctrine\DBAL\Schema\AbstractSchemaManager');
	}


	protected function execute(InputInterface $input, OutputInterface $output)
	{
		if (!$input->getOption('dump-sql') && !$input->getOption('force')) {
			$output->writeln('<error>You must run the command either with --dump-sql or --force.</error>');
			return;
		}

		if ($this->schemaManager->tablesExist($this->table)) {
			$output->writeln('Table already exists.');
			return;
		}

		$table = $this->schemaManager->createSchema()
			->createTable($this->table);
		$table->addColumn($this->key, Type::STRING);
		$table->addColumn($this->locale, Type::STRING);
		$table->addColumn($this->message, Type::TEXT);
		$table->addColumn($this->updatedAt, Type::DATETIME);
		$table->setPrimaryKey(array($this->key, $this->locale));
		$table->addIndex(array($this->updatedAt));

		if ($input->getOption('dump-sql')) {
			list($sql) = $this->connection->getDatabasePlatform()->getCreateTableSQL($table);
			$output->writeln('Create table SQL:');
			$output->writeln($sql);
		}

		if ($input->getOption('force')) {
			$this->schemaManager->createTable($table);
			$output->writeln(sprintf('Database schema updated successfully! Translation table created.'));
		}
	}

}
