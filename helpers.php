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
            'ScanIndexForward'          => false,  // newest first
            'Limit'                     => 1,
            'ProjectionExpression'      => 'expenses',
        ]);
        $items = $resp['Items'] ?? [];
        if (empty($items)) {
            return [];
        }
        return $m->unmarshalValue($items[0]['expenses'] ?? []);
    } catch (DynamoDbException $e) {
        error_log("DynamoDB error in getLatestExpenses: " . $e->getMessage());
        return [];
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