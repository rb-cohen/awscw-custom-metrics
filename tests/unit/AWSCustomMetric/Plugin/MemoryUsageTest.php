<?php

namespace AWSCustomMetric\Plugin;

use AWSCustomMetric\CommandRunner;
use AWSCustomMetric\DI;
use AWSCustomMetric\Logger\DefaultLogger;
use AWSCustomMetric\Metric;
use Codeception\Util\Stub;
use Cron\CronExpression;

class MemoryUsageTest extends \Codeception\TestCase\Test
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    protected function _before()
    {

    }

    protected function _after()
    {
    }

    public function testGetNamespace()
    {
        $memUsage = new MemoryUsage(new DI());
        $this->assertNull($memUsage->getNamespace(), 'MemoryUsage::getNamespace null test failed!');

        $memUsage = new MemoryUsage(new DI(), '');
        $this->assertEquals('', $memUsage->getNamespace(), 'MemoryUsage::getNamespace empty string failed!');

        $memUsage = new MemoryUsage(new DI(), 'MyNamespace');
        $this->assertEquals('MyNamespace', $memUsage->getNamespace(), 'MemoryUsage::getNamespace failed!');
    }

    public function testSetNamespace()
    {
        $memUsage = new MemoryUsage(new DI());
        $memUsage->setNamespace('TestSpace');
        $this->assertEquals('TestSpace', $memUsage->getNamespace(), 'MemoryUsage::setNamespace failed!');
    }

    public function testGetCronExpression()
    {
        $memUsage = new MemoryUsage(new DI());
        $this->assertNull($memUsage->getCronExpression(), 'MemoryUsage::getCronExpression null test failed!');

        $memUsage = new MemoryUsage(new DI(), '', '*/5 * * * *');
        $this->assertEquals('*/5 * * * *', $memUsage->getCronExpression(), 'MemoryUsage::getCronExpression failed!');
    }

    public function testSetCronExpression()
    {
        $memUsage = new MemoryUsage(new DI(), '');
        $memUsage->setCronExpression('*/5 * * * *');
        $this->assertEquals('*/5 * * * *', $memUsage->getCronExpression(), 'MemoryUsage::setCronExpression failed!');
    }

    public function testGetMetrics()
    {
        $diObj = new DI();

        $expectedMemMetric = new Metric();
        $expectedMemMetric->setName('MemoryUsage');
        $expectedMemMetric->setUnit('Percent');
        $expectedMemMetric->setValue('60');
        $expectedMemMetric->setNamespace('CustomMetric/Test');

        $expectedSwapMetric = new Metric();
        $expectedSwapMetric->setName('SwapUsage');
        $expectedSwapMetric->setUnit('Percent');
        $expectedSwapMetric->setValue('10');
        $expectedSwapMetric->setNamespace('CustomMetric/Test');

        /* @var CommandRunner $fakeCmdRunner */
        $fakeCmdRunner = Stub::make('\AWSCustomMetric\CommandRunner', [
            'execute' => function () {

            },
            'getReturnCode' => 0,
            'getOutput' => [
                'MemTotal:        10000 kB',
                'MemFree:          2000 kB',
                'MemAvailable:     419980 kB',
                'Buffers:          1000 kB',
                'Cached:           1000 kB',
                'SwapCached:            0 kB',
                'Active:           526652 kB',
                'Inactive:         164928 kB',
                'SwapTotal:        10000 kB',
                'SwapFree:          9000 kB',
            ]
        ]);
        $diObj->setCommandRunner($fakeCmdRunner);

        $memoryUsage = new MemoryUsage($diObj, 'CustomMetric/Test');
        $returnArray = $memoryUsage->getMetrics();
        $this->assertCount(2, $returnArray, 'Memory usage return array failed');
        $this->assertEquals(
            $expectedMemMetric,
            $returnArray[0],
            'MemoryUsage return memory usage metric object failed!'
        );
        $this->assertEquals(
            $expectedSwapMetric,
            $returnArray[1],
            'MemoryUsage return swap usage metric object failed!'
        );

        $memoryUsage->setSwapCheckOn(false);
        $returnArray = $memoryUsage->getMetrics();
        $this->assertCount(1, $returnArray, 'Memory usage swap off test failed!');
        $this->assertEquals(
            $expectedMemMetric,
            $returnArray[0],
            'MemoryUsage return memory usage metric object failed!'
        );


        $this->expectOutputString(
            "[".date('Y-m-d H:i:s')."][ERROR] /proc/meminfo parse failed!, RETVAL: 255, OUT: Error occured\n"
        );
        $fakeCmdRunner = Stub::make('\AWSCustomMetric\CommandRunner', [
            'execute' => function () {

            },
            'getReturnCode' => 255,
            'getOutput' => [
                'Error occured',
            ]
        ]);
        $diObj->setCommandRunner($fakeCmdRunner);
        $diObj->setLogger(new DefaultLogger());
        $memoryUsage   = new MemoryUsage($diObj, 'CustomMetric/Test');
        $returnArray = $memoryUsage->getMetrics();
        $this->assertFalse($returnArray, 'MemoryUsage return false failed!');
    }

    public function testGetMetricsDivisionByZero()
    {
        $diObj = new DI();

        $expectedMemMetric = new Metric();
        $expectedMemMetric->setName('MemoryUsage');
        $expectedMemMetric->setUnit('Percent');
        $expectedMemMetric->setValue('100');
        $expectedMemMetric->setNamespace('CustomMetric/Test');

        $expectedSwapMetric = new Metric();
        $expectedSwapMetric->setName('SwapUsage');
        $expectedSwapMetric->setUnit('Percent');
        $expectedSwapMetric->setValue('100');
        $expectedSwapMetric->setNamespace('CustomMetric/Test');

        /* @var CommandRunner $fakeCmdRunner */
        $fakeCmdRunner = Stub::make('\AWSCustomMetric\CommandRunner', [
            'execute' => function () {

            },
            'getReturnCode' => 0,
            'getOutput' => [
                'MemTotal:        0 kB',
                'MemFree:          2000 kB',
                'MemAvailable:     419980 kB',
                'Buffers:          1000 kB',
                'Cached:           1000 kB',
                'SwapCached:            0 kB',
                'Active:           526652 kB',
                'Inactive:         164928 kB',
                'SwapTotal:        0 kB',
                'SwapFree:          9000 kB',
            ]
        ]);
        $diObj->setCommandRunner($fakeCmdRunner);

        $memoryUsage = new MemoryUsage($diObj, 'CustomMetric/Test');
        $returnArray = $memoryUsage->getMetrics();
        $this->assertCount(2, $returnArray, 'getMetrics division by zero test failed!');
        $this->assertEquals(
            $expectedMemMetric,
            $returnArray[0],
            'getMetrics division by zero test: returned memory metric object failed!'
        );
        $this->assertEquals(
            $expectedSwapMetric,
            $returnArray[1],
            'getMetrics division by zero test: returned swap metric object failed!'
        );
    }

    public function testCreateNewMetric()
    {
        $diObj = new DI();

        $expectedMetric = new Metric();
        $expectedMetric->setName('MemoryUsage');
        $expectedMetric->setUnit('Percent');
        $expectedMetric->setValue('56');
        $expectedMetric->setNamespace('CustomMetric/Test');

        /* @var CommandRunner $fakeCmdRunner */
        $fakeCmdRunner = Stub::make('\AWSCustomMetric\CommandRunner', [
            'execute' => function () {

            },
            'getReturnCode'  => 0,
            'getReturnValue' => '56'
        ]);
        $diObj->setCommandRunner($fakeCmdRunner);

        $memUsage = new MemoryUsage($diObj, 'CustomMetric/Test');
        $this->assertEquals(
            $expectedMetric,
            $memUsage->createNewMetric('MemoryUsage', 'Percent', '56'),
            'MemoryUsage::createNewMetric test failed!'
        );
    }

    public function testSetSwapCheckOn()
    {
        $memUsage = new MemoryUsage(new DI());
        $memUsage->setSwapCheckOn(false);
        $this->assertFalse($memUsage->isSwapCheckOn(), 'MemoryUsage::setSwapCheckOn test failed!');
    }

    public function testIsSwapCheckOn()
    {
        $memUsage = new MemoryUsage(new DI());
        $this->assertTrue($memUsage->isSwapCheckOn(), 'MemoryUsage::isSwapCheckOn default true value test failed!');

        $memUsage = new MemoryUsage(new DI());
        $memUsage->setSwapCheckOn(false);
        $this->assertFalse($memUsage->isSwapCheckOn(), 'MemoryUsage::isSwapCheckOn test failed!');
    }
}
