<?php

if (!function_exists('is_valid_uuid')) {
  function is_valid_uuid($uuid): bool
  {
    return (bool) preg_match(
      '/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[1-5][0-9a-fA-F]{3}-[89abAB][0-9a-fA-F]{3}-[0-9a-fA-F]{12}$/',
      $uuid
    );
  }
}

if (!function_exists('api_response')) {
  function api_response($success, $code, $message, $data = [])
  {
    return [
      'success' => $success,
      'code'    => $code,
      'message' => $message,
      'data'    => $data
    ];
  }
}
