<?php declare(strict_types=1);

namespace Gundo\ProductInfoAgent\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class VoiceModels implements ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        $models = [
            'aura-2-thalia-en' => 'Aura 2 Thalia (English)',
            'aura-2-asteria-en' => 'Aura 2 Asteria (English)',
            'aura-2-luna-en' => 'Aura 2 Luna (English)',
            'aura-2-stella-en' => 'Aura 2 Stella (English)',
            'aura-2-athena-en' => 'Aura 2 Athena (English)',
            'aura-2-hera-en' => 'Aura 2 Hera (English)',
            'aura-2-orion-en' => 'Aura 2 Orion (English)',
            'aura-2-arcas-en' => 'Aura 2 Arcas (English)',
            'aura-2-perseus-en' => 'Aura 2 Perseus (English)',
            'aura-2-angus-en' => 'Aura 2 Angus (English)',
            'aura-2-orpheus-en' => 'Aura 2 Orpheus (English)',
            'aura-2-helios-en' => 'Aura 2 Helios (English)',
            'aura-2-zeus-en' => 'Aura 2 Zeus (English)'
        ];

        $options = [];
        foreach ($models as $value => $label) {
            $options[] = ['value' => $value, 'label' => $label];
        }

        return $options;
    }
} 