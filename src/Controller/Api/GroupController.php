<?php

namespace App\Controller\Api;

use App\Entity\Group;
use App\Entity\UserGroup;
use App\Repository\GroupRepository;
use App\Repository\UserGroupRepository;
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
use App\Form\DataTransformer\GroupToNumberTransformer;
use App\Form\DataTransformer\UserToNumberTransformer;


class GroupController extends AbstractFOSRestController
{
    private $serializer;
    private $groupRepository;
    private $userGroupRepository;
    private $groupTransformer;
    private $userTransformer;

    public function __construct(GroupRepository $groupRepository, UserGroupRepository $userGroupRepository, GroupToNumberTransformer $groupTransformer, UserToNumberTransformer $userTransformer)
    {
        $encoders = [new JsonEncoder()]; // If no need for XmlEncoder
        $normalizers = [new ObjectNormalizer()];
        $this->serializer = new Serializer($normalizers, $encoders);
        $this->groupRepository = $groupRepository;
        $this->userGroupRepository = $userGroupRepository;
        $this->groupTransformer = $groupTransformer;
        $this->userTransformer = $userTransformer;
    }

    /**
     *
     * @Rest\Get("/groups")
     */
    public function index(): View
    {
        $groups = $this->groupRepository->findAll();
        $groups = $this->serializer->normalize($groups, null, GROUP::GROUP_SERIALIZE_SCHEME);
        return View::create($groups, Response::HTTP_OK);
    }

    /**
     *
     * @Rest\Get("/group/{groupId}")
     */
    public function getSpecifiedGroup($groupId): View
    {
        $httpResponse = Response::HTTP_OK;
        $group = $this->groupRepository->find($groupId);
        if ($group) {
            $group = $this->serializer->normalize($group, null, GROUP::GROUP_SERIALIZE_SCHEME);
        } else {
            $group = 'Group with id: ' . $groupId . ' not found';
            $httpResponse = Response::HTTP_NOT_FOUND;
        }
        return View::create($group, $httpResponse);
    }

    /**
     * Creates Group
     * @Rest\Post("/group")
     * @param Request $request
     * @return View
     */
    public function newGroup(Request $request): View
    {
        $group = new Group();
        $em = $this->getDoctrine()->getManager();
        $params = $request->request->all();
        $httpStatusCode = Response::HTTP_CREATED;
        $dataInvalid = ValidatorGeneral::validateGroupData($params);
        if (count($dataInvalid) > 0) {
            return View::create($dataInvalid, Response::HTTP_BAD_REQUEST);
        }
        try {
            $group->setFullName($params['fullname']);
            $em->persist($group);
            $em->flush();
        } catch (\Exception $exception) {
            $httpStatusCode = $exception->getCode() ?: Response::HTTP_INTERNAL_SERVER_ERROR;
            $errorMessage = $exception->getMessage();
        }
        $data = isset($errorMessage) ? $errorMessage : $group;
        return View::create($data, $httpStatusCode);
    }

    /**
     * Updates Group
     * @Rest\Put("/group/{groupId}", requirements={"groupId"="\d+"})
     */
    public function updateGroup(int $groupId, Request $request): View
    {
        $em = $this->getDoctrine()->getManager();
        $httpStatusCode = Response::HTTP_OK;
        $group = $this->groupRepository->find($groupId);

        if ($group) {
            $params = $request->request->all();
            $dataInvalid = ValidatorGeneral::validateGroupData($params);

            if (count($dataInvalid) > 0) {
                return View::create($dataInvalid, Response::HTTP_BAD_REQUEST);
            }

            try {
                $group->setFullName($request->get('fullname'));
                $group = $this->serializer->normalize($group, null, GROUP::GROUP_SERIALIZE_SCHEME);
                $em->flush();
            } catch (\Exception $exception) {
                $httpStatusCode = $exception->getCode() ?: Response::HTTP_INTERNAL_SERVER_ERROR;
                $errorMessage = $exception->getMessage();
            }

        } else {
            $errorMessage = 'Group with id: ' . $groupId . ' not found';
            $httpStatusCode = Response::HTTP_NOT_FOUND;
        }

        $data = isset($errorMessage) ? $errorMessage : $group;
        return View::create($data, $httpStatusCode);
    }

    /**
     * Deletes Group
     * @param $groupId
     * @Rest\Delete("/group/{groupId}", requirements={"groupId"="\d+"})
     * @return View
     */
    public function deleteGroup(int $groupId): View
    {
        $group = $this->groupRepository->find($groupId);
        $httpStatusCode = Response::HTTP_OK;
        if ($group) {
            try {
                $em = $this->getDoctrine()->getManager();
                $em->remove($group);
                $em->flush();
            } catch (\Exception $exception) {
                $httpStatusCode = $exception->getCode() ?: Response::HTTP_INTERNAL_SERVER_ERROR;
                $errorMessage = $exception->getMessage();
            }
        } else {
            $httpStatusCode = Response::HTTP_NOT_FOUND;
            $errorMessage = 'No group with id ' . $groupId;
        }
        $data = isset($errorMessage) ? $errorMessage : 'Group id:' . $groupId . ' deleted';
        return View::create($data, $httpStatusCode);
    }

    /**
     * Adds User to Group
     * @Rest\Post("/group/add_user")
     * @param Request $request
     * @return View
     */
    public function addUserToGroup(Request $request): View
    {
        $userGroup = new UserGroup();
        $em = $this->getDoctrine()->getManager();
        $params = $request->request->all();
        $httpStatusCode = Response::HTTP_CREATED;
        $dataInvalid = ValidatorGeneral::validateUserGroupData($params);
        if (count($dataInvalid) > 0) {
            return View::create($dataInvalid, Response::HTTP_BAD_REQUEST);
        }
        try {
            $user = $this->userTransformer->reverseTransform($params['user_id']);
            $group = $this->groupTransformer->reverseTransform($params['group_id']);
            $userGroup->setUserId($user);
            $userGroup->setGroupId($group);
            $em->persist($userGroup);
            $em->flush();
            $userGroup = $this->serializer->normalize($userGroup, null, UserGroup::USER_GROUP_SERIALIZE_SCHEME);
        } catch (\Exception $exception) {
            $httpStatusCode = $exception->getCode() ?: Response::HTTP_INTERNAL_SERVER_ERROR;
            $errorMessage = $exception->getMessage();
        }
        $data = isset($errorMessage) ? $errorMessage : $userGroup;
        return View::create($data, $httpStatusCode);
    }

    /**
     * Removes User from Group
     * @param Request $request
     * @Rest\Delete("/group/remove_user")
     * @return View
     */
    public function removeUserFromGroup(Request $request): View
    {
        $em = $this->getDoctrine()->getManager();
        $params = $request->request->all();
        $httpStatusCode = Response::HTTP_OK;
        $dataInvalid = ValidatorGeneral::validateUserGroupData($params);
        if (count($dataInvalid) > 0) {
            return View::create($dataInvalid, Response::HTTP_BAD_REQUEST);
        }
        $userGroup = $this->userGroupRepository->findOneBy(array('userId' => $params['user_id'], 'groupId' => $params['group_id']));
        if ($userGroup) {
            try {
                $em->remove($userGroup);
                $em->flush();
            } catch (\Exception $exception) {
                $httpStatusCode = $exception->getCode() ?: Response::HTTP_INTERNAL_SERVER_ERROR;
                $errorMessage = $exception->getMessage();
            }
        } else {
            $httpStatusCode = Response::HTTP_NOT_FOUND;
            $errorMessage = 'User id: ' . $params['user_id'] . ' is not member of Group id: ' . $params['group_id'];
        }
        $data = isset($errorMessage) ? $errorMessage : 'User id: ' . $params['user_id'] . ' removed from Group id: ' . $params['group_id'];
        return View::create($data, $httpStatusCode);
    }

}
