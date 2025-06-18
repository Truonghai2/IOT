<?php

namespace App\Http\Controllers;

class Controller
{
    protected function jsonResponse($data, $status = 200)
    {
        header('Content-Type: application/json');
        http_response_code($status);
        echo json_encode($data);
        exit;
    }

    protected function validate($request, array $rules)
    {
        $errors = [];
        foreach ($rules as $field => $rule) {
            $value = $request->input($field);
            
            if (strpos($rule, 'required') !== false && empty($value)) {
                $errors[$field][] = "The {$field} field is required.";
            }
            
            if (strpos($rule, 'string') !== false && !is_string($value)) {
                $errors[$field][] = "The {$field} must be a string.";
            }
            
            if (strpos($rule, 'max:') !== false) {
                preg_match('/max:(\d+)/', $rule, $matches);
                $max = $matches[1];
                if (strlen($value) > $max) {
                    $errors[$field][] = "The {$field} may not be greater than {$max} characters.";
                }
            }
            
            if (strpos($rule, 'unique:') !== false) {
                preg_match('/unique:([^,]+)(?:,([^,]+))?(?:,(\d+))?/', $rule, $matches);
                $table = $matches[1];
                $column = $matches[2] ?? $field;
                $except = $matches[3] ?? null;
                
                // TODO: Implement unique validation
            }
        }
        
        return (object)['fails' => function() use ($errors) {
            return !empty($errors);
        }, 'errors' => (object)$errors];
    }
} 