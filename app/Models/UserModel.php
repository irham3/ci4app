<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
  protected $table         = 'users';
  protected $primaryKey    = 'user_id';
  protected $returnType    = 'array';
  protected $useTimestamps = true;
  protected $createdField  = 'created_at';
  protected $updatedField  = 'updated_at';

  protected $allowedFields = [
    'user_id',
    'fullname',
    'email',
    'phone',
    'gender',
    'image',
    'password',
    'has_membership',
    'qr_code'
  ];

  protected $validationRules = [
    'fullname'        => 'required|max_length[100]',
    'email'           => 'required|valid_email|max_length[100]|is_unique[users.email,user_id,{user_id}]',
    'phone'           => 'required|max_length[20]',
    'gender'          => 'permit_empty|in_list[male,female,other]',
    'image'           => 'permit_empty|max_length[255]',
    'password'        => 'required|min_length[6]',
    'has_membership'  => 'permit_empty|in_list[0,1]',
    'qr_code'         => 'permit_empty|max_length[255]',
  ];
}
