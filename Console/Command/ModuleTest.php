<?php

namespace Gundo\ProductInfoAgent\Console\Command;

use Gundo\ProductInfoAgent\Model\ProductInfoAgent as CollectionFactory;
use Magento\Framework\Console\Cli;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ModuleTest extends Command
{
    /**
     * @var CollectionFactory
     */
    private CollectionFactory $collectionFactory;

    /**
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(CollectionFactory $collectionFactory)
    {
        $this->collectionFactory = $collectionFactory;
        parent::__construct();
    }

    /**
     * Initialization of the command.
     */
    protected function configure()
    {
        $this->setName('agent:product:into:test')
            ->setDescription('Test Insert Command for ProductInfoAgent');
        parent::configure();
    }

    /**
     * CLI command description.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {

            $item = $this->collectionFactory;
            $item->getCollection();

            $output->writeln('Inserted Product ID: ');
            $output->writeln(' Message: ' . $item->getMessage());
            $output->writeln(' User: '. $item->getUserId());
        } catch (\Exception $e) {
            $output->writeln('<error>Error inserting item: ' . $e->getMessage() . '</error>');
            return Cli::RETURN_FAILURE;
        }

        return Cli::RETURN_SUCCESS;
    }
}
