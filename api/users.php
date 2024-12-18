<?php
require_once __DIR__ . '/ApiHandler.php';
require_once __DIR__ . '/../classes/User.php';

class UsersApi extends ApiHandler {
    private $userModel;

    public function __construct() {
        parent::__construct();
        $this->userModel = new User();
    }

    public function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];
        $action = $_GET['action'] ?? '';

        // All endpoints except profile require admin
        if ($action !== 'profile') {
            $this->requireAdmin();
        } else {
            $this->requireAuth();
        }

        switch ($method) {
            case 'GET':
                if ($action === 'profile') {
                    $this->getProfile();
                } else {
                    $id = $_GET['id'] ?? null;
                    $id ? $this->getUser($id) : $this->getAllUsers();
                }
                break;

            case 'POST':
                $this->createUser();
                break;

            case 'PUT':
                if ($action === 'profile') {
                    $this->updateProfile();
                } else {
                    $this->updateUser();
                }
                break;

            case 'DELETE':
                $this->deleteUser();
                break;

            default:
                $this->sendError('Method not allowed', 405);
        }
    }

    private function getAllUsers() {
        try {
            $users = $this->userModel->findAll([], 'created_at DESC');
            // Remove password hashes from response
            array_walk($users, function(&$user) {
                unset($user['password']);
            });
            $this->sendResponse(['users' => $users]);
        } catch (Exception $e) {
            $this->sendError($e->getMessage());
        }
    }

    private function getUser($id) {
        try {
            $user = $this->userModel->findById($id);
            if (!$user) {
                $this->sendError('User not found', 404);
            }
            unset($user['password']);
            $this->sendResponse(['user' => $user]);
        } catch (Exception $e) {
            $this->sendError($e->getMessage());
        }
    }

    private function getProfile() {
        try {
            $user = $this->userModel->findById($_SESSION['user_id']);
            if (!$user) {
                $this->sendError('User not found', 404);
            }
            unset($user['password']);
            $this->sendResponse(['profile' => $user]);
        } catch (Exception $e) {
            $this->sendError($e->getMessage());
        }
    }

    private function createUser() {
        $data = $this->getRequestData();
        $this->validateRequired($data, ['username', 'password', 'name', 'role']);

        try {
            // Hash password
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            
            $userId = $this->userModel->create($data);
            if (!$userId) {
                $this->sendError('Failed to create user');
            }

            $user = $this->userModel->findById($userId);
            unset($user['password']);
            $this->sendResponse(['user' => $user], 201);
        } catch (Exception $e) {
            $this->sendError($e->getMessage());
        }
    }

    private function updateUser() {
        $data = $this->getRequestData();
        $this->validateRequired($data, ['id']);

        try {
            // If password is being updated, hash it
            if (!empty($data['password'])) {
                $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }

            if (!$this->userModel->update($data['id'], $data)) {
                $this->sendError('Failed to update user');
            }

            $user = $this->userModel->findById($data['id']);
            unset($user['password']);
            $this->sendResponse(['user' => $user]);
        } catch (Exception $e) {
            $this->sendError($e->getMessage());
        }
    }

    private function updateProfile() {
        $data = $this->getRequestData();
        $userId = $_SESSION['user_id'];

        try {
            // Users can only update their name and password
            $allowedFields = ['name', 'password'];
            $updateData = array_intersect_key($data, array_flip($allowedFields));

            if (!empty($updateData['password'])) {
                $updateData['password'] = password_hash($updateData['password'], PASSWORD_DEFAULT);
            }

            if (!$this->userModel->update($userId, $updateData)) {
                $this->sendError('Failed to update profile');
            }

            $user = $this->userModel->findById($userId);
            unset($user['password']);
            $this->sendResponse(['profile' => $user]);
        } catch (Exception $e) {
            $this->sendError($e->getMessage());
        }
    }

    private function deleteUser() {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            $this->sendError('User ID is required');
        }

        // Prevent deleting own account
        if ($id == $_SESSION['user_id']) {
            $this->sendError('Cannot delete your own account');
        }

        try {
            if (!$this->userModel->delete($id)) {
                $this->sendError('Failed to delete user');
            }
            $this->sendResponse(['message' => 'User deleted successfully']);
        } catch (Exception $e) {
            $this->sendError($e->getMessage());
        }
    }
}

// Handle the request
$api = new UsersApi();
$api->handleRequest();
