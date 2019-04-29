<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Repository\UserRepository;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use App\Validators\ValidatorGeneral;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;


class UserController extends AbstractFOSRestController
{
    private $serializer;
    private $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $encoders = [new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];
        $this->serializer = new Serializer($normalizers, $encoders);
        $this->userRepository = $userRepository;
    }

    /**
     * Gets list of all users
     * @Rest\Get("/users")
     */
    public function index(): View
    {
        $users = $this->userRepository->findAll();
        $users = $this->serializer->normalize($users, null, User::USER_SERIALIZE_SCHEME);
        return View::create($users, Response::HTTP_OK);
    }

    /**
     * Gets current user info
     * @Rest\Get("/user/who_am_i")
     */
    public function whoAmI(): View
    {
        $user = $this->getUser();
        $user = $this->serializer->normalize($user, null, User::USER_SERIALIZE_SCHEME);
        return View::create($user, Response::HTTP_OK);
    }

    /**
     * Gets specified user data
     * @Rest\Get("/user/{userId}", requirements={"userId"="\d+"})
     * @param int $userId
     * @return View
     */
    public function getSpecifiedUser($userId): View
    {
        $user = $this->userRepository->find($userId);
        $user = $this->serializer->normalize($user, null, User::USER_SERIALIZE_SCHEME);
        return View::create($user, Response::HTTP_OK);
    }

    /**
     * Creates User
     * @Rest\Post("/user")
     * @param Request $request
     * @param ValidatorInterface $validator
     * @param UserPasswordEncoderInterface $encoder
     * @return View
     */
    public function newUser(Request $request, ValidatorInterface $validator, UserPasswordEncoderInterface $encoder): View
    {
        $user = new User();
        $em = $this->getDoctrine()->getManager();
        $params = $request->request->all();
        $httpStatusCode = Response::HTTP_CREATED;
        $dataInvalid = $this->validateUserData($params, $validator);
        if (count($dataInvalid) > 0) {
            return View::create($dataInvalid, Response::HTTP_BAD_REQUEST);
        }
        try {
            $user->setEmail($params['email']);
            $user->setRoles($params['role']);
            $encodedPassword = $encoder->encodePassword($user, $params['password']);
            $user->setPassword($encodedPassword);
            $apiToken = $user->generateApiToken();
            $user->setApiToken($apiToken);
            $user->setName($params['name']);
            $em->persist($user);
            $em->flush();
        } catch (\Exception $exception) {
            $httpStatusCode = $exception->getCode() ?: Response::HTTP_INTERNAL_SERVER_ERROR;
            $errorMessage = $exception->getMessage();
        }
        $data = isset($errorMessage) ? $errorMessage : $user;
        return View::create($data, $httpStatusCode);
    }

    /**
     * Updates User
     * @Rest\Put("/user/{userId}", requirements={"userId"="\d+"})
     * @param int $userId
     * @param Request $request
     * @param ValidatorInterface $validator
     * @param UserPasswordEncoderInterface $encoder
     * @return View
     */
    public function updateUser(int $userId, Request $request, UserPasswordEncoderInterface $encoder, ValidatorInterface $validator): View
    {
        $em = $this->getDoctrine()->getManager();
        $user = $this->userRepository->find($userId);
        $params = $request->request->all();
        $httpStatusCode = Response::HTTP_OK;
        $dataInvalid = $this->validateUserData($params, $validator);

        if (count($dataInvalid) > 0) {
            return View::create($dataInvalid, Response::HTTP_BAD_REQUEST);
        }

        if ($user) {
            try {
                if (isset($params['email'])) {
                    $user->setEmail($params['email']);
                    $apiToken = $user->generateApiToken();
                    $user->setApiToken($apiToken);
                }
                if (isset($params['name'])) {
                    $user->setName($params['name']);
                }
                if (isset($params['role'])) {
                    $user->setRoles($params['role']);
                }
                if (isset($params['password'])) {
                    $encodedPassword = $encoder->encodePassword($user, $params['password']);
                    $user->setPassword($encodedPassword);
                }
                $user = $this->serializer->normalize($user, null, USER::USER_SERIALIZE_SCHEME);
                $em->flush();
            } catch (\Exception $exception) {
                $httpStatusCode = $exception->getCode() ?: Response::HTTP_INTERNAL_SERVER_ERROR;
                $errorMessage = $exception->getMessage();
            }
        } else {
            $httpStatusCode = Response::HTTP_NOT_FOUND;
            $errorMessage = 'No user with id ' . $userId;
        }

        $data = isset($errorMessage) ? $errorMessage : $user;
        return View::create($data, $httpStatusCode);
    }

    /**
     * Deletes User
     * @Rest\Delete("/user/{userId}", requirements={"userId"="\d+"})
     * @param int $userId
     * @return View
     */
    public function deleteUser(int $userId): View
    {
        $user = $this->userRepository->find($userId);
        $httpStatusCode = Response::HTTP_OK;
        if ($user) {
            try {
                $em = $this->getDoctrine()->getManager();
                $em->remove($user);
                $em->flush();
            } catch (\Exception $exception) {
                $httpStatusCode = $exception->getCode() ?: Response::HTTP_INTERNAL_SERVER_ERROR;
                $errorMessage = $exception->getMessage();
            }
        } else {
            $httpStatusCode = Response::HTTP_NOT_FOUND;
            $errorMessage = 'No user with id ' . $userId;
        }
        $data = isset($errorMessage) ? $errorMessage : 'User id:' . $userId . ' deleted';
        return View::create($data, $httpStatusCode);
    }

    /**
     * Validates email and role fields
     * @param array $data
     * @param ValidatorInterface $validator
     * @return array
     */
    private function validateUserData($data, ValidatorInterface $validator) {
        $errors = [];
        $emailInvalid = isset($data['email']) ? ValidatorGeneral::validateEmail($data['email'], $validator) : null;
        $roleInvalid = isset($data['role']) ? ValidatorGeneral::validateRole($data['role']) : null;

        if (!empty($emailInvalid)) {
            $errors['email_error'] = $emailInvalid;
        }

        if (!empty($roleInvalid)) {
            $errors['role_error'] = $roleInvalid;
        }

        return $errors;
    }
}
