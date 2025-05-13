<?php 
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\AssertionFailedError;

require __DIR__ . '/../vendor/autoload.php';

final class Test_generate_forecast extends TestCase {
    public function test_generate_forecast() {
        // Test Case 1
        // Sample data
        $sample_1 = [
            [1500, 290, 600, 400, 2129],
            [8990, 2003, 599, 21, 49000],
            [23, 95, 211, 443, 90],
            [10, 550, 320, 534, 98899],
            [335455, 23134, 998, 90, 39999]
        ];

        // Expected result
        $expected_1 = [
            [1394, 1531, 1667, 1804, 1941],
            [35534, 43337, 51141, 58945, 66749],
            [317, 365, 413, 461, 509],
            [79391, 99167, 118943, 138719, 158496],
            [-104251, -165647, -227042, -288438, -349834]
        ];


        // Generate forecasts
        foreach ($sample_1 as $i => $s1) {
            $forecast = generate_forecast($s1, 5);
            $this->assertEqualsWithDelta($expected_1[$i], $forecast, 1,"Forecast mismatch at index $i");
        }


        // Test Case 2
        // Sample data
        $sample_2 = [
            [0.4438735, 34.42546, 7.7656, 432.499, 9.09389982],
            [000.4324995, 9.3432, 0996.00993, 999.43885, 1.2322100],
            [3004.43, 9.0943, 43922.299895, 995.99030, 3.0000001],
        ];

        // Expected result
        $expected_2 = [
            [221, 262, 304, 346, 387],
            [698, 797, 897, 996, 1095],
            [8082, 7580, 7078, 6577, 6075],
        ];


        // Generate forecasts
        foreach ($sample_2 as $i => $s2) {
            $forecast = generate_forecast($s2, 5);
            $this->assertEqualsWithDelta($expected_2[$i], $forecast, 1,"Forecast mismatch at index $i");
        }


        // Test Case 3
        // Sample data
        $sample_3 = [
            [-0.00094, -0.998843, 989324.09, 8.00001, 434.00457],
            [293.3884844, 98.0000001, -5.023883, 0.00132434, -40.039282],
            [6.3432002, 838784.994, -9.020993, 8.3288344, 9.099322],
            [-0.1233444, -0.94, 0.000001344, -1.3994, 65.549900]
        ];

        // Expected result
        $expected_3 = [
            [198216, 198303, 198391, 198479, 198566],
            [-160, -236, -313, -389, -466],
            [-83871, -167748, -251625, -335502, -419379],
            [51, 64, 78, 91, 104],
        ];


        // Generate forecasts
        foreach ($sample_3 as $i => $s3) {
            $forecast = generate_forecast($s3, 5);
            $this->assertEqualsWithDelta($expected_3[$i], $forecast, 1,"Forecast mismatch at index $i");
        }
    }
}

function generate_forecast($historical_data, $periods) {
    // Simple forecasting using linear regression
    $n = count($historical_data);
    if ($n < 2) return array_fill(0, $periods, end($historical_data));
    
    $sumX = $sumY = $sumXY = $sumX2 = 0;
    
    foreach ($historical_data as $i => $value) {
        $x = $i + 1;
        $sumX += $x;
        $sumY += $value;
        $sumXY += $x * $value;
        $sumX2 += $x * $x;
    }
    
    $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
    $intercept = ($sumY - $slope * $sumX) / $n;
    
    $forecast = [];
    for ($i = 1; $i <= $periods; $i++) {
        $forecast[] = $intercept + $slope * ($n + $i);
    }
    
    return $forecast;
}


