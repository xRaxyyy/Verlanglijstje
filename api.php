<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$dataFile = 'wishlist-data.json';

// Handelt OPTIONS requests voor CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

// Haal items op
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (file_exists($dataFile)) {
        $data = file_get_contents($dataFile);
        echo $data;
    } else {
        echo json_encode([]);
    }
    exit;
}

// Voeg nieuw item toe of update items
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if ($input === null) {
        http_response_code(400);
        echo json_encode(['error' => 'Ongeldige JSON data']);
        exit;
    }
    
    // Valideer de data
    if (!isset($input['items']) && !isset($input['action'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Ongeldige data structuur']);
        exit;
    }
    
    // Als het een volledige lijst is (voor opslaan)
    if (isset($input['items'])) {
        file_put_contents($dataFile, json_encode($input['items']));
        echo json_encode(['success' => true]);
    } 
    // Specifieke acties (toevoegen, verwijderen, etc.)
    else {
        $action = $input['action'];
        $items = file_exists($dataFile) ? json_decode(file_get_contents($dataFile), true) : [];
        
        switch ($action) {
            case 'add':
                $newItem = $input['item'];
                $newItem['id'] = time();
                $newItem['purchaseCount'] = 0;
                $items[] = $newItem;
                break;
                
            case 'delete':
                $items = array_filter($items, fn($item) => $item['id'] !== $input['id']);
                $items = array_values($items); // Reset array keys
                break;
                
            case 'increment':
                $items = array_map(function($item) use ($input) {
                    if ($item['id'] === $input['id']) {
                        $item['purchaseCount']++;
                    }
                    return $item;
                }, $items);
                break;
                
            case 'decrement':
                $items = array_map(function($item) use ($input) {
                    if ($item['id'] === $input['id'] && $item['purchaseCount'] > 0) {
                        $item['purchaseCount']--;
                    }
                    return $item;
                }, $items);
                break;
        }
        
        file_put_contents($dataFile, json_encode($items));
        echo json_encode(['success' => true, 'items' => $items]);
    }
    
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
?>
