<?php

declare(strict_types=1);

namespace Jpmschuler\TvplusContentslide\Tests\Acceptance;

/**
 * Test case.
 */
class CrossLanguageCest
{
    public function _before(\AcceptanceTester $I): void
    {
        $I->amOnPage('/');
    }

    public function seeSlideContent(\AcceptanceTester $I): void
    {
        $I->see('SlideContent');
    }
}
