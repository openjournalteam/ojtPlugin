<?php 

namespace Openjournalteam\OjtPlugin\Traits;

use JSONMessage;

trait ApiPostValidate
{
    public function validatePostRequest($request)
    {
        // header('Content-Type: application/json');
        
        // Validate the request method
        if (!$request->isPost()) {
            header('Content-Type: application/json');
            http_response_code(405); // Method Not Allowed
            echo json_encode(['success' => false, 'message' => 'This endpoint only accepts POST requests.']);
            exit;
        }
    }

    private function getAuthorizationHeader()
    {
        $headers = null;
        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER["Authorization"]);
        } else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            //print_r($requestHeaders);
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
        return $headers;
    }

    public function validateBearerToken()
    {
        $getBearerToken = $this->getAuthorizationHeader();
        
        if (empty($getBearerToken)) {
            header('Content-Type: application/json');
            http_response_code(401); // Unauthorized
            echo json_encode(['success' => false, 'message' => 'Authorization header is missing or empty.']);
            exit;
        }

        if (!str_contains($getBearerToken, 'Bearer ')) {
            header('Content-Type: application/json');
            http_response_code(401); // Unauthorized
            echo json_encode(['success' => false, 'message' => 'Invalid authorization format. Expected "Bearer {token}".']);
            exit;
        }

        $getBearerToken = str_replace('Bearer ', '', $getBearerToken);

        return $getBearerToken;
    }
}