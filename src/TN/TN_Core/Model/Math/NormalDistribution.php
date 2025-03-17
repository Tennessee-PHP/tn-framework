<?php

namespace TN\TN_Core\Model\Math;

/**
 * normal distribution probability
 * 
 */
class NormalDistribution
{
    /**
     * @param float $z the bound to test
     * @param float $m the median
     * @param float $sd the standard deviation
     * @return float
     */
   public static function probability(float $z, float $m, float $sd): float
   {
        $sd = abs($sd);
        if ($sd === 0) {
            return $z < $m ? 0 : 1;
        }

        return self::normalCdf(($z - $m) / $sd);
   }

    /**
     * @param float $x
     * @return float
     */
    public static function normalCdf(float $x): float
    {
        $t = 1 / (1 + 0.2316419 * abs($x));
        $d = 0.3989423 * exp((0 - $x) * $x/2);
        $p = $d * $t * (0.3193815 + $t * (-0.3565638 + $t * (1.781478 + $t * (-1.821256 + $t * 1.330274))));
        if ($x > 0) {
            $p = 1 - $p;
        }
        return $p;
    }

}
