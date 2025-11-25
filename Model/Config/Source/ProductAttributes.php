<?php declare(strict_types=1);

namespace Gundo\ProductInfoAgent\Model\Config\Source;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Option\ArrayInterface;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;

class ProductAttributes implements ArrayInterface
{
    const UNDEFINED_OPTION_LABEL = '-- Please Select --';

    /**
     * @var AttributeRepositoryInterface
     */
    private $attributeRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @param AttributeRepositoryInterface $attributeRepository
     * @param SearchCriteriaBuilder        $searchCriteriaBuilder
     */
    public function __construct(
        AttributeRepositoryInterface $attributeRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->attributeRepository = $attributeRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        $options = [['value' => '', 'label' => __(self::UNDEFINED_OPTION_LABEL)]];

        try {
            $searchCriteria = $this->searchCriteriaBuilder->create();
            $attributes = $this->attributeRepository->getList(\Magento\Catalog\Model\Product::ENTITY, $searchCriteria);

            foreach ($attributes->getItems() as $attribute) {
                $options[] = [
                    'value' => $attribute->getAttributeCode(),
                    'label' => $attribute->getAttributeCode()
                ];
            }
        } catch (LocalizedException $e) {
            unset($e);
        }

        return $options;
    }
}
