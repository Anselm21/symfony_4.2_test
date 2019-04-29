<?php

namespace App\Validators;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Entity\User;

/**
 * Validation methods helper class
 */
class ValidatorGeneral
{
    public static function validateEmail($email, ValidatorInterface $validator) {
        $emailConstraint = new Assert\Email();
        $emailConstraint->message = 'Invalid email address';
        //null means successful validation
        $errorMessage = null;

        $errors = $validator->validate(
            $email,
            $emailConstraint
        );

        if (count($errors) > 0) {
            $errorMessage = $errors[0]->getMessage();
        }
        return $errorMessage;
    }

    public static function validateRole($role) {
        $errorMessage = null;
        foreach ($role as $key => $value) {
            if ($value !== USER::ROLE_USER && $value !== USER::ROLE_ADMIN) {
                $errorMessage = 'Invalid role value';
                break;
            }
        }
        return $errorMessage;
    }

    public static function validateGroupData($data) {
        $errors = [];
        $nameInvalid = empty($data['fullname']) ? 'fullname is a required parameter' : null;
        if (!empty($nameInvalid)) {
            $errors['name_error'] = $nameInvalid;
        }
        return $errors;
    }

    public static function validateUserGroupData($data) {
        $errors = [];
        $userIdInvalid = empty($data['user_id']) ? 'user_id is a required parameter' : null;
        $groupIdInvalid = empty($data['group_id']) ? 'group_id is a required parameter' : null;

        if (!empty($userIdInvalid)) {
            $errors['user_id_error'] = $userIdInvalid;
        }
        if (!empty($groupIdInvalid)) {
            $errors['group_id_error'] = $groupIdInvalid;
        }
        return $errors;
    }

}
