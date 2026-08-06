<?php

namespace Tests\Unit;

use App\Models\SiteParameter;
use PHPUnit\Framework\TestCase;

/**
 * SiteParameter::isWarn/isCritical/isOutOfRange drive every "Warning" /
 * "Critical" badge on the dashboard. A regression here silently hides a real
 * alert (e.g. a panel overheating) instead of just breaking a UI label, so
 * this is covered as a pure unit test — no DB needed, the model only reads
 * its own attributes.
 */
class SiteParameterThresholdTest extends TestCase
{
    private function param(array $attrs): SiteParameter
    {
        return new SiteParameter(array_merge([
            'threshold_direction' => 'below',
        ], $attrs));
    }

    public function test_below_direction_warns_at_or_under_threshold(): void
    {
        $p = $this->param(['warning_threshold' => 20, 'threshold_direction' => 'below']);

        $this->assertTrue($p->isWarn(20));   // at threshold
        $this->assertTrue($p->isWarn(15));   // under threshold
        $this->assertFalse($p->isWarn(25));  // above threshold — fine
    }

    public function test_above_direction_warns_at_or_over_threshold(): void
    {
        // Panel temperature: high is bad, not low — this is the exact bug
        // the threshold_direction field was introduced to fix.
        $p = $this->param(['warning_threshold' => 70, 'threshold_direction' => 'above']);

        $this->assertTrue($p->isWarn(70));   // at threshold
        $this->assertTrue($p->isWarn(85));   // over threshold — overheating
        $this->assertFalse($p->isWarn(50));  // under threshold — fine
    }

    public function test_critical_threshold_is_independent_of_warning_threshold(): void
    {
        $p = $this->param([
            'warning_threshold'  => 30,
            'critical_threshold' => 10,
            'threshold_direction' => 'below',
        ]);

        $this->assertTrue($p->isWarn(20));      // below warning, above critical
        $this->assertFalse($p->isCritical(20));
        $this->assertTrue($p->isCritical(5));   // below both
        $this->assertTrue($p->isWarn(5));
    }

    public function test_null_or_non_numeric_value_never_warns(): void
    {
        $p = $this->param(['warning_threshold' => 20]);

        $this->assertFalse($p->isWarn(null));
        $this->assertFalse($p->isWarn('n/a'));
        $this->assertFalse($p->isCritical(null));
    }

    public function test_unset_threshold_never_warns(): void
    {
        $p = $this->param(['warning_threshold' => null]);

        $this->assertFalse($p->isWarn(5));
        $this->assertFalse($p->isWarn(500));
    }

    public function test_is_out_of_range_checks_min_and_max_independently_of_thresholds(): void
    {
        $p = $this->param(['min_value' => 0, 'max_value' => 100]);

        $this->assertFalse($p->isOutOfRange(50));
        $this->assertTrue($p->isOutOfRange(-1));
        $this->assertTrue($p->isOutOfRange(101));
        $this->assertFalse($p->isOutOfRange(0));    // boundary inclusive
        $this->assertFalse($p->isOutOfRange(100));  // boundary inclusive
    }

    public function test_is_out_of_range_ignores_unset_bounds(): void
    {
        $p = $this->param(['min_value' => 0, 'max_value' => null]);

        $this->assertFalse($p->isOutOfRange(999999)); // no max configured
        $this->assertTrue($p->isOutOfRange(-1));       // min still enforced
    }

    public function test_default_direction_is_below_when_not_set_on_the_model(): void
    {
        // Simulates a legacy row created before threshold_direction existed —
        // the DB column defaults to 'below', so a bare attribute array without
        // it should behave the same as explicit 'below'.
        $p = new SiteParameter(['warning_threshold' => 20]);

        $this->assertTrue($p->isWarn(10));
        $this->assertFalse($p->isWarn(30));
    }
}
