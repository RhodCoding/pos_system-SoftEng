<?php
session_start();
require_once '../classes/Employee.php';

header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$employee = new Employee();
$response = ['success' => false];

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            if (isset($_GET['id'])) {
                $employeeData = $employee->getById($_GET['id']);
                if ($employeeData) {
                    $response = ['success' => true, 'employee' => $employeeData];
                } else {
                    $response = ['success' => false, 'message' => 'Employee not found'];
                }
            } else {
                $employees = $employee->getAllEmployees();
                $response = ['success' => true, 'employees' => $employees];
            }
            break;

        case 'POST':
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                $response = ['success' => false, 'message' => 'Invalid data provided'];
                break;
            }

            $result = $employee->create($data);
            $response = $result;
            break;

        case 'PUT':
            $data = json_decode(file_get_contents('php://input'), true);
            if (!isset($data['id'])) {
                $response = ['success' => false, 'message' => 'Employee ID is required'];
                break;
            }

            if ($employee->update($data['id'], $data)) {
                $response = ['success' => true, 'message' => 'Employee updated successfully'];
            } else {
                $response = ['success' => false, 'message' => 'Failed to update employee'];
            }
            break;

        case 'DELETE':
            if (!isset($_GET['id'])) {
                $response = ['success' => false, 'message' => 'Employee ID is required'];
                break;
            }

            if ($employee->delete($_GET['id'])) {
                $response = ['success' => true, 'message' => 'Employee deleted successfully'];
            } else {
                $response = ['success' => false, 'message' => 'Failed to delete employee'];
            }
            break;

        default:
            $response = ['success' => false, 'message' => 'Invalid request method'];
            break;
    }
} catch (Exception $e) {
    error_log('Error in employees.php: ' . $e->getMessage());
    $response = ['success' => false, 'message' => $e->getMessage()];
}

echo json_encode($response);
