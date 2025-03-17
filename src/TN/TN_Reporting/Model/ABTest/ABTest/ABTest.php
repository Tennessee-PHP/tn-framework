<?php

namespace TN\TN_Reporting\Model\ABTest\ABTest;

use TN\TN_Core\Model\Request\HTTPRequest;
use TN\TN_Reporting\Model\ABTest\ABTestVariant\ABTestVariant;
use TN\TN_Reporting\Model\TrackedVisitor\TrackedVisitor;

abstract class ABTest
{
    use \TN\TN_Core\Trait\Getter;
    use \TN\TN_Core\Trait\ExtendedSingletons;

    /** @var float how much statistical significance do we require to settle the test? */
    const SIGNIFICANCE_REQUIRED = 0.95;

    /** @var string non-db identifier */
    protected string $key;

    /** @var string base directory for all variant templates */
    protected string $tplDir;

    /** @var ABTestVariant[] the variants. set these on the sub-class constructor */
    protected array $variants;

    /** @var string[] the class string of the route which indicates success, when hit */
    protected array $successRoutes;

    /** @var bool is it active? */
    protected bool $active = true;

    /** @var bool hopefully, we can prepare this before we get to the template */
    private bool $prepared = false;

    /** @var bool do we have enough data to have a settled decision? */
    protected bool $settled = false;

    /** @var ABTestVariant the variant we've selected to show to the user */
    private ABTestVariant $selectedVariant;

    /**
     * @return int
     */
    protected function getVariantFrequencyCount(): int
    {
        $count = 0;
        foreach ($this->variants as $variant) {
            $count += $variant->frequency;
        }
        return $count;
    }

    /**
     * @param int $num
     * @return ABTestVariant
     */
    protected function getVariantFrom1ToFrequency(int $num): ABTestVariant
    {
        foreach ($this->variants as $variant) {
            if ($num <= 0 && $variant->frequency > 0) {
                return $variant;
            }
            $num -= $variant->frequency;
        }
        return $this->variants[0];
    }

    public function prepare(): ABTest
    {
        $this->selectedVariant = $this->getVariant();
        $this->prepared = true;
        return $this;
    }

    /**
     * @param string $variantTemplate
     * @return ABTestVariant|null
     */
    public function getVariantByTemplate(string $variantTemplate): ?ABTestVariant
    {
        foreach ($this->variants as $variant) {
            if ($variant->template === $variantTemplate) {
                return $variant;
            }
        }
        return null;
    }

    /**
     * decide upon and then get a variant
     * @return ABTestVariant
     */
    private function getVariant(): ABTestVariant
    {
        $sessionKey = 'ABTest-selectedvariant:' . $this->key;
        $request = HTTPRequest::get();
        $variantQuery = $request->getQuery('variant');
        if (!empty($variantQuery)) {
            foreach ($this->variants as $variant) {
                if (strtolower($variant->template) === strtolower($variantQuery)) {
                    $request->setSession($sessionKey, $variant->template);
                    $variant->registerView();
                    return $variant;
                }
            }
        }

        // if settled, only $_GET above should ever return something else!
        if ($this->settled) {
            foreach ($this->variants as $variant) {
                if ($variant->winner) {
                    return $variant;
                }
            }
        }

        // already on the session?
        $sessionValue = $request->getSession($sessionKey);
        if (!empty($sessionValue)) {
            foreach ($this->variants as $variant) {
                if (strtolower($variant->template) === strtolower($sessionValue)) {
                    $variant->registerView();
                    return $variant;
                }
            }
        }

        // well ok, let's settle this by deciding randomly!
        $trackedVisitor = TrackedVisitor::getInstance();
        $variant = $this->getVariantFrom1ToFrequency(rand(1, 100) % $this->getVariantFrequencyCount());
        $request->setSession($sessionKey, $variant->template);

        $variant->registerView();
        return $variant;
    }

    /**
     * @return void
     */
    private function registerSuccess(): void
    {
        if ($this->settled) {
            return;
        }
        // they must specifically have a data point with the currently selected variant
        $variant = $this->getVariant();
        $variant->registerSuccess();
    }

    /**
     * analyze data set on variants
     * @return void
     */
    public function analyzeData(): void
    {
        if (count($this->variants) <= 1) {
            return;
        }

        // sort the variants by conversion percentage
        $conversionPcs = [];
        $variants = [];
        foreach ($this->variants as $variant) {
            $conversionPcs[] = $variant->conversionPercentage;
            $variants[] = $variant;
        }
        array_multisort($conversionPcs, SORT_DESC, $variants);
        $bestVariant = $variants[0];
        $nextVariant = $variants[1];
        $bestVariant->isBestOption = true;

        $zScore = $this->zScore(
            [$nextVariant->views, $nextVariant->successes],
            [$bestVariant->views, $bestVariant->successes]
        );
        $bestVariant->statisticalSignificance = $this->cumulativeNormalDistribution($zScore);

        $this->settled = $bestVariant->statisticalSignificance >= self::SIGNIFICANCE_REQUIRED;
    }

    /**
     * @param array $t
     * @return float
     */
    private function cr(array $t): float
    {
        if ($t[0] === 0) {
            return 0.0;
        }
        return $t[1]/$t[0];
    }

    /**
     * @param array $c
     * @param array $t
     * @return float
     */
    private function zScore(array $c, array $t): float
    {
        if ($c[0] === 0 || $t[0] === 0) {
            return 0.0;
        }
        $z = $this->cr($t)-$this->cr($c);
        $s = ($this->cr($t)*(1-$this->cr($t)))/$t[0] +
            ($this->cr($c)*(1-$this->cr($c)))/$c[0];
        if (sqrt($s) === 0.0) {
            return 0.0;
        }
        return $z/sqrt($s);
    }

    private function cumulativeNormalDistribution(float $x): float
    {
        $b1 =  0.319381530;
        $b2 = -0.356563782;
        $b3 =  1.781477937;
        $b4 = -1.821255978;
        $b5 =  1.330274429;
        $p  =  0.2316419;
        $c  =  0.39894228;

        if($x >= 0.0) {
            $t = 1.0 / ( 1.0 + $p * $x );
            return (1.0 - $c * exp( -$x * $x / 2.0 ) * $t *
                ( $t *( $t * ( $t * ( $t * $b5 + $b4 ) + $b3 ) + $b2 ) + $b1 ));
        }
        else {
            $t = 1.0 / ( 1.0 - $p * $x );
            return ( $c * exp( -$x * $x / 2.0 ) * $t *
                ( $t *( $t * ( $t * ( $t * $b5 + $b4 ) + $b3 ) + $b2 ) + $b1 ));
        }
    }

    /**
     * treat a route class!
     * @param string $route
     * @return string
     */
    private static function treatRouteString(string $route): string
    {
        return trim($route, '\\');
    }

    /**
     * gets the template string for a decided variant, given an ABTest $key
     * @param string $key the key of the ABTest to get a template for
     * @return string|null
     */
    public static function getVariantTemplate(string $key): ?string
    {
        $abTest = ABTest::getInstanceByKey($key);
        if (!$abTest) {
            return null;
        }
        if (!$abTest->prepared) {
            $abTest->prepare();
        }
        return $abTest->tplDir . '/' . $abTest->selectedVariant->template . '.tpl';
    }

    public static function registerABTestSuccesses(string $successRoute): void
    {
        // let's be sure to trim
        $successRoute = self::treatRouteString($successRoute);
        foreach (self::getInstances() as $abTest) {
            foreach ($abTest->successRoutes as $abTestSuccessRoute) {
                if (self::treatRouteString($abTestSuccessRoute) === $successRoute) {
                    $abTest->registerSuccess();
                }
            }

        }
    }
}