<?php
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Exception\DynamoDbException;
use Aws\DynamoDb\Marshaler;

function getLatestExpenses(DynamoDbClient $client, Marshaler $m, string $email): array {
    $cId = hash('sha256', $email);
    try {
        $resp = $client->query([
            'TableName'                 => 'Finance',
            'IndexName'                 => 'c_id-created_at-index',
            'KeyConditionExpression'    => 'c_id = :cid',
            'ExpressionAttributeValues' => $m->marshalItem([':cid' => $cId]),
            'ScanIndexForward'          => false,
            'Limit'                     => 1,
            'ProjectionExpression'      => 'expenses, budgets',
        ]);

        $item = $resp['Items'][0] ?? null;
        if (! $item) {
            return ['expenses' => [], 'budgets' => []];
        }

        $expenses = [];
        if (isset($item['expenses'])) {
            $expenses = $m->unmarshalValue($item['expenses']);
        }

        $budgets = [];
        if (isset($item['budgets'])) {
            $budgets = $m->unmarshalValue($item['budgets']);
        }

        return [
            'expenses' => $expenses,
            'budgets'  => $budgets,
        ];
    } catch (DynamoDbException $e) {
        error_log("DynamoDB error in getLatestExpenses: " . $e->getMessage());
        return ['expenses' => [], 'budgets' => []];
    }
}

function fetchSpendingTrend(DynamoDbClient $client, Marshaler $m, string $email, int $months = 6): array {
    $cId = hash('sha256', $email);
    try {
        $resp = $client->query([
            'TableName'                 => 'Finance',
            'IndexName'                 => 'c_id-created_at-index',
            'KeyConditionExpression'    => 'c_id = :cid',
            'ExpressionAttributeValues' => $m->marshalItem([':cid' => $cId]),
            'ScanIndexForward'          => false,
            'Limit'                     => $months,
        ]);

        $items = array_reverse($resp['Items'] ?? []);
        if (empty($items)) {
            return [[], []];
        }

        $labels = [];
        $data   = [];
        foreach ($items as $item) {
            // Label: month from created_at
            if (isset($item['created_at'])) {
                $dateStr = $m->unmarshalValue($item['created_at']);
                $dt      = new DateTime($dateStr);
            } else {
                $dt = new DateTime();
            }
            $labels[] = $dt->format('M');

            // Data: sum of expenses map
            if (isset($item['expenses'])) {
                $raw = $m->unmarshalValue($item['expenses']);
            } else {
                $raw = [];
            }
            $sumValue = array_sum(array_map('floatval', $raw));
            $data[]   = $sumValue;
        }

        return [$labels, $data];
    } catch (DynamoDbException $e) {
        error_log("DynamoDB error in fetchSpendingTrend: " . $e->getMessage());
        return [[], []];
    }
}

function fetchIncomeTrend(DynamoDbClient $client, Marshaler $m, string $email, int $months = 6): array {
    $cId = hash('sha256', $email);
    try {
        $resp = $client->query([
            'TableName'                 => 'Finance',
            'IndexName'                 => 'c_id-created_at-index',
            'KeyConditionExpression'    => 'c_id = :cid',
            'ExpressionAttributeValues' => $m->marshalItem([':cid' => $cId]),
            'ScanIndexForward'          => false,
            'Limit'                     => $months,
        ]);

        $items  = array_reverse($resp['Items'] ?? []);
        $labels = [];
        $data   = [];
        foreach ($items as $item) {
            // Label
            if (isset($item['created_at'])) {
                $label = (new DateTime($m->unmarshalValue($item['created_at'])))->format('M');
            } else {
                $label = (new DateTime())->format('M');
            }
            $labels[] = $label;

            // Income value
            if (isset($item['income'])) {
                $value = floatval($m->unmarshalValue($item['income']));
            } else {
                $value = 0.0;
            }
            $data[] = $value;
        }

        return [$labels, $data];
    } catch (DynamoDbException $e) {
        error_log("DynamoDB error in fetchIncomeTrend: " . $e->getMessage());
        return [[], []];
    }
}