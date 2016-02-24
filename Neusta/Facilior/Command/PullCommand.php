<?php
/**
 * Created by PhpStorm.
 * User: nlotzer
 * Date: 05.02.2016
 * Time: 08:24
 */

namespace Neusta\Facilior\Command;


use Neusta\Facilior\Database;
use Neusta\Facilior\Environment;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;

class PullCommand extends AbstractCommand
{

    /**
     * @var null|InputInterface
     */
    protected $inputInterface = null;

    /**
     * @param InputInterface $input
     * @return bool
     */
    protected function execute(InputInterface $input)
    {
        $this->inputInterface = $input;

        $sourceEnvironment = $input->getArgument('from');
        $sourceEnvironment = Environment::get($sourceEnvironment);

        $destEnvironment = $this->getDestinationEnvironment();
        $destEnvironment = Environment::get($destEnvironment);

        $sourceDatabase = new Database($sourceEnvironment);
        $destinationDatabase = new Database($destEnvironment);

        $this->consoleOutput->output('Starting pulling <fg=magenta>' . $input->getArgument('from') . '</> database...');
        $sqlDumpPath = $sourceDatabase->exportSql();

        if ($sourceDatabase->isLastCommandFailed()) {
            $this->consoleOutput->output('<fg=red>Error!!</> Pulling <fg=magenta>' .
                $input->getArgument('from') . '</> wasn\'t successfull.');
            $this->consoleOutput->output('<fg=default>Please</> check your Logs for more Information.');
            return -1;
        } else {
            $this->consoleOutput->output('<fg=green>Success!!</> Pulling <fg=magenta>' .
                $input->getArgument('from') . '</> was successfull.');
        }


        $this->consoleOutput->output('Starting importing <fg=magenta>' .
            $this->getDestinationEnvironment() . '</> database...');
        $destinationDatabase->importSql($sqlDumpPath);

        if ($destinationDatabase->isLastCommandFailed()) {
            $this->consoleOutput->output('<fg=red>Error!!</> Importing <fg=magenta>' .
                $this->getDestinationEnvironment() . '</> wasn\'t successfull.');
            $this->consoleOutput->output('<fg=default>Please</> check your Logs for more Information.');
            return -1;
        } else {
            $this->consoleOutput->output('<fg=green>Success!!</> Importing <fg=magenta>' . $this->getDestinationEnvironment() . '</> was successfull.');
        }


        return 0;
    }

    /**
     * sets settings for command
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('pull')
            ->setDescription('Does the magic.')
            ->addArgument('from', InputArgument::REQUIRED, 'Which Source Database should be used?')
            ->addArgument(
                'to',
                InputArgument::OPTIONAL,
                'Destination Database. Default is defined in the general config'
            );
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    protected function getDestinationEnvironment()
    {
        if (!empty($this->inputInterface->getArgument('to'))) {
            return $this->inputInterface->getArgument('to');
        }

        if (!empty($this->generalConfig['local_environment'])) {
            return $this->generalConfig['local_environment'];
        }

        throw new \Exception('Cannot resolve destination database.');
    }

}