<?php namespace Hokuken\Haik\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ServeCommand extends Command {

    protected function configure()
    {
        $this->setName('serve')
             ->setDescription('Start haik server')
             ->addOption(
                'host',
                null,
                InputOption::VALUE_REQUIRED,
                'The host address to serve the application on.',
                'localhost'
             )
             ->addOption(
                'port',
                null,
                InputOption::VALUE_REQUIRED,
                'The port to serve the application on.',
                8000
             );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
		$this->checkPhpVersion();

		$host = $input->getOption('host');

		$port = $input->getOption('port');

		$output->writeln("<info>haik development server started on http://{$host}:{$port}</info>");

		passthru('"'.PHP_BINARY.'"'." -S {$host}:{$port} server.php");
    }

	protected function checkPhpVersion()
	{
		if (version_compare(PHP_VERSION, '5.4.0', '<'))
		{
			throw new \Exception('This PHP binary is not version 5.4 or greater.');
		}
	}

}
