<?php

namespace App\Controllers\Api\V1;

use App\Controllers\BaseController;
use App\Models\UserModel;
use Ramsey\Uuid\Uuid;
use CodeIgniter\API\ResponseTrait;

helper('api');

class Users extends BaseController
{
  use ResponseTrait;

  // Add this property to define required parameters and rules
  protected $validationRules = [
    'create' => [
      'fullname' => 'required|max_length[100]',
      'email' => 'required|valid_email|max_length[100]|is_unique[users.email]',
      'phone' => 'required|numeric|max_length[15]|is_unique[users.phone]',
      'gender' => 'required|in_list[male,female]',
      'password' => 'required|min_length[6]',
      'image' => 'permit_empty|max_length[255]',
      'has_membership' => 'permit_empty|in_list[0,1]',
      'qr_code' => 'permit_empty|max_length[255]'
    ],
    'update' => [
      'fullname' => 'permit_empty|max_length[100]',
      'email' => 'permit_empty|valid_email|max_length[100]|is_unique[users.email,user_id,{user_id}]',
      'phone' => 'permit_empty|numeric|max_length[15]|is_unique[users.phone,user_id,{user_id}]',
      'gender' => 'permit_empty|in_list[male,female]',
      'password' => 'permit_empty|min_length[6]',
      'image' => 'permit_empty|max_length[255]',
      'has_membership' => 'permit_empty|in_list[0,1]',
      'qr_code' => 'permit_empty|max_length[255]'
    ]
  ];

  public function getValidationRules($action)
  {
    $rules = $this->validationRules[$action] ?? [];

    return [
      'required_parameters' => array_keys(array_filter($rules, function ($rule) {
        return strpos($rule, 'required') !== false;
      })),
      'all_parameters' => array_keys($rules),
      'validation_rules' => $rules
    ];
  }

  public function index()
  {
    $model = new UserModel();
    $users = $model->findAll();

    return $this->respond([
      'success' => true,
      'code'    => 200,
      'data'    => $users,
      'message' => 'Users retrieved successfully'
    ]);
  }

  public function show($id = null)
  {
    if (!is_valid_uuid($id)) {
      return $this->respond([
        'success' => false,
        'code'    => 400,
        'message' => 'Invalid UUID format'
      ], 400);
    }

    $model = new UserModel();
    $user = $model->find($id);

    if (!$user) {
      return $this->respond([
        'success' => false,
        'code'    => 404,
        'message' => 'User not found'
      ], 404);
    }

    // Remove sensitive data before response
    unset($user['password']);

    return $this->respond([
      'success' => true,
      'code'    => 200,
      'data'    => $user,
      'message' => 'User retrieved successfully'
    ]);
  }

  public function create()
  {
    $model = new UserModel();
    $data = $this->request->getJSON(true);

    // Get validation rules and allowed parameters
    $validationInfo = $this->getValidationRules('create');
    $requiredParams = $validationInfo['required_parameters'];
    $allowedParams = $validationInfo['all_parameters'];

    // Check if no data provided at all
    if (empty($data)) {
      return $this->respond([
        'success' => false,
        'code'    => 400,
        'message' => 'No parameters provided',
        'validation_info' => [
          'required_parameters' => $requiredParams,
          'allowed_parameters' => $allowedParams,
          'message' => 'You must provide all required parameters',
          'sample_request' => $this->getSampleRequest('create')
        ]
      ], 400);
    }

    // Check for additional/unallowed parameters
    $unallowedParams = array_diff(array_keys($data), $allowedParams);
    if (!empty($unallowedParams)) {
      return $this->respond([
        'success' => false,
        'code'    => 400,
        'message' => 'Invalid parameters provided',
        'unallowed_parameters' => array_values($unallowedParams),
        'validation_info' => [
          'allowed_parameters' => $allowedParams,
          'message' => 'The following parameters are not allowed',
          'sample_request' => $this->getSampleRequest('create')
        ]
      ], 400);
    }

    // Check for missing required parameters
    $missingParams = array_diff($requiredParams, array_keys($data));
    if (!empty($missingParams)) {
      return $this->respond([
        'success' => false,
        'code'    => 400,
        'message' => 'Missing required parameters',
        'missing_parameters' => array_values($missingParams),
        'validation_info' => [
          'required_parameters' => $requiredParams,
          'allowed_parameters' => $allowedParams,
          'message' => 'The following parameters are required but missing',
          'sample_request' => $this->getSampleRequest('create')
        ]
      ], 400);
    }

    // Validate empty values for required parameters
    $emptyParams = [];
    foreach ($requiredParams as $param) {
      if (empty($data[$param])) {
        $emptyParams[] = $param;
      }
    }
    if (!empty($emptyParams)) {
      return $this->respond([
        'success' => false,
        'code'    => 400,
        'message' => 'Empty values for required parameters',
        'empty_parameters' => $emptyParams,
        'validation_info' => [
          'required_parameters' => $requiredParams,
          'message' => 'The following parameters cannot be empty',
          'sample_request' => $this->getSampleRequest('create')
        ]
      ], 400);
    }

    // Process valid data
    $data['user_id'] = Uuid::uuid4()->toString();
    $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);

    // Model validation
    if (!$model->validate($data)) {
      return $this->respond([
        'success' => false,
        'code'    => 422,
        'message' => 'Validation failed',
        'errors'  => $model->errors(),
        'validation_info' => [
          'required_parameters' => $requiredParams,
          'allowed_parameters' => $allowedParams,
          'validation_rules' => $validationInfo['validation_rules'],
          'sample_request' => $this->getSampleRequest('create')
        ]
      ], 422);
    }

    try {
      $model->insert($data);
      return $this->respondCreated([
        'success' => true,
        'code'    => 201,
        'data'    => ['user_id' => $data['user_id']],
        'message' => 'User created successfully'
      ]);
    } catch (\Exception $e) {
      return $this->respond([
        'success' => false,
        'code'    => 500,
        'message' => 'Failed to create user',
        'error'   => $e->getMessage()
      ], 500);
    }
  }

  protected function getSampleRequest($action)
  {
    $samples = [
      'create' => [
        'fullname' => 'John Doe',
        'email' => 'john@example.com',
        'phone' => '08123456789',
        'gender' => 'male',
        'password' => 'secret123',
        'image' => 'profile.jpg',
        'has_membership' => 1,
        'qr_code' => 'ABCD1234'
      ],
      'update' => [
        'fullname' => 'Updated Name',
        'email' => 'new-email@example.com',
        'phone' => '08987654321',
        'gender' => 'female',
        'password' => 'newpassword123',
        'image' => 'new-profile.jpg'
      ]
    ];

    return $samples[$action] ?? [];
  }

  public function update($id = null)
  {
    $model = new UserModel();

    if (!is_valid_uuid($id)) {
      return $this->respond([
        'success' => false,
        'code'    => 400,
        'message' => 'Invalid UUID format',
        'validation_info' => [
          'required_format' => 'UUID v4',
          'example' => '550e8400-e29b-41d4-a716-446655440000'
        ]
      ], 400);
    }

    $existing = $model->find($id);
    if (!$existing) {
      return $this->respond([
        'success' => false,
        'code'    => 404,
        'message' => 'User not found'
      ], 404);
    }

    $data = $this->request->getJSON(true);
    if (empty($data)) {
      $validationInfo = $this->getValidationRules('update');
      return $this->respond([
        'success' => false,
        'code'    => 400,
        'message' => 'No data provided for update',
        'validation_info' => [
          'allowed_parameters' => $validationInfo['all_parameters'],
          'validation_rules' => $validationInfo['validation_rules'],
          'sample_request' => [
            'fullname' => 'Updated Name',
            'email' => 'new-email@example.com',
            'phone' => '08987654321'
          ]
        ]
      ], 400);
    }

    // Don't allow changing user_id
    if (isset($data['user_id'])) {
      unset($data['user_id']);
    }

    // Handle password update
    if (!empty($data['password'])) {
      $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
    } else {
      unset($data['password']);
    }

    // Validate data for update
    if (!$model->validateUpdate($data, $id)) {
      $validationInfo = $this->getValidationRules('update');
      return $this->respond([
        'success' => false,
        'code'    => 422,
        'message' => 'Validation failed',
        'errors'  => $model->errors(),
        'validation_info' => [
          'allowed_parameters' => $validationInfo['all_parameters'],
          'validation_rules' => $validationInfo['validation_rules']
        ]
      ], 422);
    }

    try {
      $model->update($id, $data);
      return $this->respond([
        'success' => true,
        'code'    => 200,
        'message' => 'User updated successfully',
        'data'    => [
          'user_id' => $id,
          'updated_fields' => array_keys($data)
        ]
      ]);
    } catch (\Exception $e) {
      return $this->respond([
        'success' => false,
        'code'    => 500,
        'message' => 'Failed to update user',
        'error'   => $e->getMessage()
      ], 500);
    }
  }

  public function delete($id = null)
  {
    $model = new UserModel();

    if (!is_valid_uuid($id)) {
      return $this->respond([
        'success' => false,
        'code'    => 400,
        'message' => 'Invalid UUID format'
      ], 400);
    }

    $user = $model->find($id);
    if (!$user) {
      return $this->respond([
        'success' => false,
        'code'    => 404,
        'message' => 'User not found'
      ], 404);
    }

    try {
      $model->delete($id);
      return $this->respondDeleted([
        'success' => true,
        'code'    => 200,
        'message' => 'User deleted successfully'
      ]);
    } catch (\Exception $e) {
      return $this->respond([
        'success' => false,
        'code'    => 500,
        'message' => 'Failed to delete user',
        'error'   => $e->getMessage()
      ], 500);
    }
  }
}
