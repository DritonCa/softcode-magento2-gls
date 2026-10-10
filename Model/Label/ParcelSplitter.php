<?php
declare(strict_types=1);

namespace Softcode\Gls\Model\Label;

/**
 * Splits a total weight into equal parcels no heavier than the configured maximum,
 * e.g. 20 kg with a 15 kg max becomes 2 parcels of 10 kg.
 */
class ParcelSplitter
{
    /** Hard cap that guards against a mis-set weight limit creating hundreds of labels. */
    public const MAX_PARCELS = 20;

    private const MIN_WEIGHT = 0.1;

    /**
     * @return float[] weight (kg) of each parcel
     */
    public function split(float $totalWeight, float $maxPerParcel): array
    {
        $total = max(self::MIN_WEIGHT, round($totalWeight, 1));
        $count = 1;
        if ($maxPerParcel > 0 && $total > $maxPerParcel) {
            $count = (int)min(self::MAX_PARCELS, (int)ceil($total / $maxPerParcel));
        }
        $weight = max(self::MIN_WEIGHT, round($total / $count, 1));
        return array_fill(0, $count, $weight);
    }
}
