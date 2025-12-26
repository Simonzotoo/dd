<?php
require_once 'config.php';

header('Content-Type: application/json');

$action = isset($_GET['action']) ? sanitizeInput($_GET['action']) : '';

switch ($action) {
    case 'get_events':
        getUpcomingEvents();
        break;
    case 'get_registrations':
        getEventRegistrations();
        break;
    case 'export_data':
        exportData();
        break;
    default:
        jsonResponse(false, 'Invalid API action');
}

function getUpcomingEvents() {
    $conn = getDBConnection();
    
    // In a real app, this would query events from database
    // For now, return sample data
    $events = [
        [
            'id' => 1,
            'title' => 'Virtual Networking: FinTech Professionals',
            'date' => '2025-03-25',
            'time' => '18:00',
            'type' => 'virtual',
            'registered' => 45
        ],
        [
            'id' => 2,
            'title' => 'Diaspora Investment Summit Accra 2025',
            'date' => '2025-03-28',
            'time' => '09:00',
            'type' => 'in-person',
            'registered' => 120
        ]
    ];
    
    jsonResponse(true, 'Events retrieved', $events);
}

function getEventRegistrations() {
    // This would be protected with authentication in production
    $conn = getDBConnection();
    
    $event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;
    
    if ($event_id > 0) {
        $stmt = $conn->prepare("
            SELECT id, full_name, email, location, profession, 
                   registration_date, status 
            FROM event_registrations 
            WHERE id = ?
        ");
        $stmt->bind_param("i", $event_id);
    } else {
        $stmt = $conn->prepare("
            SELECT id, full_name, email, event_name, 
                   registration_date, status 
            FROM event_registrations 
            ORDER BY registration_date DESC 
            LIMIT 50
        ");
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $registrations = [];
    while ($row = $result->fetch_assoc()) {
        $registrations[] = $row;
    }
    
    $stmt->close();
    $conn->close();
    
    jsonResponse(true, 'Registrations retrieved', $registrations);
}

function exportData() {
    $type = isset($_GET['type']) ? sanitizeInput($_GET['type']) : 'events';
    $conn = getDBConnection();
    
    switch ($type) {
        case 'events':
            $stmt = $conn->prepare("SELECT * FROM event_registrations");
            $filename = 'event_registrations_' . date('Y-m-d') . '.json';
            break;
        case 'newsletter':
            $stmt = $conn->prepare("SELECT * FROM newsletter_subscribers WHERE is_active = 1");
            $filename = 'newsletter_subscribers_' . date('Y-m-d') . '.json';
            break;
        case 'contacts':
            $stmt = $conn->prepare("SELECT * FROM contact_inquiries");
            $filename = 'contact_inquiries_' . date('Y-m-d') . '.json';
            break;
        default:
            jsonResponse(false, 'Invalid export type');
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    $stmt->close();
    $conn->close();
    
    // Set headers for download
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit();
}
?>