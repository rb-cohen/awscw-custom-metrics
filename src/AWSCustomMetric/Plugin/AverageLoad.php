<?php

namespace AWSCustomMetric\Plugin;

use AWSCustomMetric\Metric;

class AverageLoad extends BaseMetricPlugin implements MetricPluginInterface
{
    /**
     * @return Metric[]|null
     */
    public function getMetrics()
    {
        $this->diObj->getCommandRunner()->execute('uname -s');
        $osName = $this->diObj->getCommandRunner()->getReturnValue();
        switch ($osName) {
            case 'Darwin':
                $delimiter = ' ';
                $this->diObj->getCommandRunner()->execute("/bin/uptime | awk -F'[a-z]:' '{ print $2}'");
                break;
            case 'Linux':
            default:
                $delimiter = ', ';
                $this->diObj->getCommandRunner()->execute("/usr/bin/uptime | awk -F'[a-z]:' '{ print $2}'");
                break;
        }

        $averageString= $this->diObj->getCommandRunner()->getReturnValue();
        $averages = explode($delimiter, trim($averageString));

        if(count($averages) === 3) {
            return [ $this->createNewMetric('LoadAverage', 'None', (float) $averages[0]) ];
        } else {
            return null;
        }
    }
}
