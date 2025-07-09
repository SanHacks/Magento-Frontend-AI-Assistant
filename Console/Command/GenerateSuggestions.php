<?php

declare(strict_types=1);

namespace Gundo\ProductInfoAgent\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Gundo\ProductInfoAgent\Api\SuggestionInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\State as AppState;
use Magento\Store\Model\StoreManagerInterface;

class GenerateSuggestions extends Command
{
    private const OPTION_PRODUCTS = 'product-id';
    private const OPTION_ALL = 'all';

    private SuggestionInterface $suggestionGenerator;
    private ProductRepositoryInterface $productRepository;
    private SearchCriteriaBuilder $searchCriteriaBuilder;
    private AppState $appState;

    public function __construct(
        SuggestionInterface $suggestionGenerator,
        ProductRepositoryInterface $productRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        AppState $appState
    ) {
        $this->suggestionGenerator = $suggestionGenerator;
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->appState = $appState;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('gundo:product-agent:generate-suggestions')
            ->setDescription('Generates suggested questions for products.')
            ->addOption(self::OPTION_PRODUCTS, null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Product IDs to generate for')
            ->addOption(self::OPTION_ALL, null, InputOption::VALUE_NONE, 'Generate for all products');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->appState->setAreaCode('adminhtml');

        $productIds = $input->getOption(self::OPTION_PRODUCTS);
        $allProducts = $input->getOption(self::OPTION_ALL);

        if (!$productIds && !$allProducts) {
            $output->writeln('<error>Please specify product IDs using --product-id or use --all.</error>');
            return Command::FAILURE;
        }

        $searchCriteria = $this->searchCriteriaBuilder;
        if ($productIds) {
            $searchCriteria->addFilter('entity_id', $productIds, 'in');
        }

        $products = $this->productRepository->getList($searchCriteria->create())->getItems();

        foreach ($products as $product) {
            try {
                $output->writeln("<info>Generating suggestions for product ID: {$product->getId()} (SKU: {$product->getSku()})...</info>");
                $this->suggestionGenerator->getSuggestions((int)$product->getId());
            } catch (\Exception $e) {
                $output->writeln("<error>Failed for product ID {$product->getId()}: {$e->getMessage()}</error>");
            }
        }

        $output->writeln('<info>Suggestion generation complete.</info>');
        return Command::SUCCESS;
    }
} 