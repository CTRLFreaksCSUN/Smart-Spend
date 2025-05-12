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

        // Dynamo returns an array under 'Items'
        $item = $resp['Items'][0] ?? null;
        if (! $item) {
            return ['expenses'=>[], 'budgets'=>[]];
        }

        return [
            'expenses' => $m->unmarshalValue($item['expenses'] ?? []),
            'budgets'  => $m->unmarshalValue($item['budgets']  ?? []),
        ];
    } catch (DynamoDbException $e) {
        error_log("DynamoDB error in getLatestExpenses: " . $e->getMessage());
        return ['expenses'=>[], 'budgets'=>[]];
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
            'ScanIndexForward'          => false,  // newest first
            'Limit'                     => $months,
        ]);

        // Grab and reverse the Items
        $items = array_reverse($resp['Items'] ?? []);
        if (empty($items)) {
            return [[], []];
        }

        $labels = $data = [];
        foreach ($items as $item) {
            $dt    = new DateTime($m->unmarshalValue($item['created_at']));
            $labels[] = $dt->format('M');
            $raw     = $m->unmarshalValue($item['expenses'] ?? []);
            $data[]  = array_sum(array_map('floatval', $raw));
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
        $items = array_reverse($resp['Items'] ?? []);
        $labels = $data = [];
        foreach ($items as $item) {
            $dt    = new DateTime($m->unmarshalValue($item['created_at']));
            $labels[] = $dt->format('M');
            // here we look up the stored “income” attribute
            $data[]  = floatval($m->unmarshalValue($item['income'] ?? 0));
        }
        return [$labels, $data];
    } catch (DynamoDbException $e) {
        error_log("DynamoDB error in fetchIncomeTrend: " . $e->getMessage());
        return [[], []];
    }
}