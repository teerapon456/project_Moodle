<?php

class BaseController
{
    /**
     * Process incoming API requests by dispatching to the appropriate method
     * based on the 'action' query parameter.
     */
    public function processRequest()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $action = $_GET['action'] ?? null;

        // Get the input data
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        // If specific method exists for the action, call it
        if ($action && method_exists($this, $action)) {
            try {
                // If it's a POST/PUT request, pass data. If GET, maybe just call it.
                // However, most existing methods in BookingController like 'create' expect arguments.
                // We need to adapt based on how the methods are defined.

                // Reflection to check parameters could be safer, 
                // but for now let's assume methods that need data take an array or are no-arg.

                // HACK: To be compatible with existing controllers, we need to see how they are called.
                // BookingController->create(array $data)
                // CarController->create(array $data)

                // If the method expects an array, pass $input.
                $reflection = new ReflectionMethod($this, $action);
                $params = $reflection->getParameters();

                if (count($params) > 0) {
                    $response = $this->$action($input);
                } else {
                    $response = $this->$action();
                }

                // If response is returned (and not just echoed), send it as JSON
                if ($response !== null) {
                    // Check if headers already sent
                    if (!headers_sent()) {
                        // header('Content-Type: application/json'); // Already set in routes.php usually
                    }
                    echo json_encode($response);
                }
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        } else {
            // Default behavior or 404
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => "Action '$action' not found found in controller."]);
        }
    }

    /**
     * Helper to send JSON error response
     */
    protected function sendError($message, $code = 400)
    {
        http_response_code($code);
        return ['success' => false, 'message' => $message];
    }

    /**
     * Helper to send JSON success response
     */
    protected function sendSuccess($data = [], $message = 'Success')
    {
        return array_merge(['success' => true, 'message' => $message], $data);
    }
}
