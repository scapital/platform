<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Unit\Behat\Specification\Stub;

use Behat\Gherkin\Node\FeatureNode;
use Behat\Testwork\Specification\Locator\SpecificationLocator;
use Behat\Testwork\Specification\SpecificationArrayIterator;
use Behat\Testwork\Suite\Suite;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class SpecificationLocatorFilesystemStub implements SpecificationLocator
{
    /**
     * {@inheritdoc}
     */
    public function getLocatorExamples()
    {
        return 'Return iterator with features found in paths specified in constructor. For unit tests only';
    }

    /**
     * {@inheritdoc}
     */
    public function locateSpecifications(Suite $suite, $locator = null)
    {
        $features = [];

        foreach ($suite->getSetting('paths') as $path) {
            $features[] = new FeatureNode(null, null, [], null, [], '', '', $path, 0);
        }

        return new SpecificationArrayIterator($suite, $features);
    }
}
