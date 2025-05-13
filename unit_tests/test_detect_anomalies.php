<?php 
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\AssertionFailedError;

require __DIR__ . '/../vendor/autoload.php';

final class Test_detect_anomalies extends TestCase {
    public function test_detect_anomalies() {
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
            [0, 0, 0, 0, 0],
            [0, 0, 0, 0, 49000],
            [0, 0, 0, 0, 443],
            [0, 0, 0, 0, 98899],
            [0, 0, 0, 0, 335455]
        ];


        // Generate forecasts
        $anomalies = [];
        foreach ($sample_1 as $i => $s1) {
            $anomalies[$i] = detect_anomalies($s1);
        }

        foreach($anomalies as $i => $a) {
            $this->assertEqualsWithDelta($expected_1[$i], $a, 1,"Mismatch at index $i");
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
            [0, 0, 0, 0, 432],
            [0, 0, 0, 0, 0],
            [0, 0, 0, 0, 43922],
        ];


        // Generate forecasts
        $anomalies = [];
        foreach ($sample_2 as $i => $s2) {
            $anomalies[$i] = detect_anomalies($s2);
        }

        foreach($anomalies as $i => $a) {
            $this->assertEqualsWithDelta($expected_2[$i], $a, 1,"Mismatch at index $i");
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
            [0, 0, 0, 0, 989324],
            [0, 0, 0, 0, 293],
            [-9, 0, 0, 0, 838784],
            [0, 0, 0, 0, 65],
        ];

        // Generate forecasts
        $anomalies = [];
        foreach ($sample_3 as $i => $s3) {
            $anomalies[$i] = detect_anomalies($s3);
        }

        foreach($anomalies as $i => $a) {
            $this->assertEqualsWithDelta($expected_3[$i], $a, 1,"Mismatch at index $i");
        }
    }
}

function detect_anomalies($data) {
    // Simple anomaly detection using IQR method
    if (count($data) < 3) return [];
    
    sort($data);
    $q1 = $data[floor(count($data) * 0.25)];
    $q3 = $data[floor(count($data) * 0.75)];
    $iqr = $q3 - $q1;
    
    $lower_bound = $q1 - 1.5 * $iqr;
    $upper_bound = $q3 + 1.5 * $iqr;
    
    $anomalies = [];
    foreach ($data as $value) {
        if ($value < $lower_bound || $value > $upper_bound) {
            $anomalies[] = $value;
        }
        else {
            $anomalies[] = 0;
        }
    }
    
    return $anomalies;
}

