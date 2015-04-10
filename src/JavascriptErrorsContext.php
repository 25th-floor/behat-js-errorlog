<?php

namespace TwentyFifth\Behat\JsErrorLog;

use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\AfterStepScope;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Mink\Mink;
use Behat\MinkExtension\Context\MinkAwareContext;

/**
 * catches js errors and reports them
 */
class JavascriptErrorsContext implements Context, MinkAwareContext
{
    const IGNORE_TAG = 'ignore-js-error';

    /** @var  Mink Specifics */
    private $mink;
    private $minkParameters;

    /** @var bool is context enabled on this scenario */
    private $lookForErrors = false;

    /** @var string Scenario Information for error output */
    private $scenarioData;

    /**
     * Sets Mink instance.
     *
     * @param Mink $mink Mink session manager
     */
    public function setMink(Mink $mink)
    {
        $this->mink = $mink;
    }

    /**
     * Returns Mink instance.
     *
     * @return Mink
     */
    public function getMink()
    {
        return $this->mink;
    }

    /**
     * Returns the parameters provided for Mink.
     *
     * @return array
     */
    public function getMinkParameters()
    {
        return $this->minkParameters;
    }

    /**
     * Sets parameters provided for Mink.
     *
     * @param array $parameters
     */
    public function setMinkParameters(array $parameters)
    {
        $this->minkParameters = $parameters;
    }

    /**
     * check if we should enable looking for js errors
     *
     * @BeforeScenario
     *
     * @param BeforeScenarioScope $scope
     */
    public function prepare(BeforeScenarioScope $scope)
    {
        if ($this->getMink() == null) {
            return;
        }

        if ($this->getMink()->getDefaultSessionName() != 'selenium2') {
            return;
        }

        // no need to do something if this is an empty scenario
        if (!$scope->getScenario()->hasSteps() && !$scope->getFeature()->hasBackground()) {
            return;
        }

        // ignore scenarios with the ignore tag
        if ($scope->getScenario()->hasTag(self::IGNORE_TAG) || $scope->getFeature()->hasTag(self::IGNORE_TAG)) {
            return;
        }

        $this->lookForErrors = true;
        $this->scenarioData = basename($scope->getFeature()->getFile()) . '.' . $scope->getScenario()->getLine();
    }

    /**
     * @AfterStep
     *
     * @param AfterStepScope $scope
     *
     * @throws \Exception
     */
    public function lookForJSErrors(AfterStepScope $scope)
    {
        if (!$this->lookForErrors) {
            return;
        }

        $driver = $this->getMink()->getSession()->getDriver();

        try {
            $errors = $driver->evaluateScript("return window.jsErrors");
        } catch (\Exception $e) {
            // output where the error occurred for debugging purposes
            echo $this->scenarioData;
            throw $e;
        }

        if (!$errors || empty($errors)) {
            return;
        }

        $file = sprintf("%s:%d", $scope->getFeature()->getFile(), $scope->getStep()->getLine());
        $message = sprintf("Found %d javascript error%s", count($errors), count($errors) > 0 ? 's' : '');

        echo '-------------------------------------------------------------' . PHP_EOL;
        echo $file . PHP_EOL;
        echo $message . PHP_EOL;
        echo '-------------------------------------------------------------' . PHP_EOL;

        foreach ($errors as $index => $error) {
            echo sprintf("   #%d: %s", $index, $error) . PHP_EOL;
        }

        throw new \Exception($message);
    }

}
